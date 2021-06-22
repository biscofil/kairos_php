<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Models\Election;
use App\Models\Question;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Throwable;

/**
 * TODO call QuestionType isDecryptedBallotValid for each ballot question
 * Class TallyDatabase
 * @package App\Voting\AnonymizationMethods\MixNets
 * @property \App\Models\Election $election
 * @property string $pathname
 * @property SQLiteConnection $connection
 */
class TallyDatabase
{

    public Election $election;
    public string $pathname;
    public SQLiteConnection $connection;

    /**
     * TallyDatabase constructor.
     * @param \App\Models\Election $election
     * @throws \Exception
     */
    public function __construct(Election $election)
    {
        $this->election = $election;
        $this->pathname = $this->election->getOutputDatabaseStorageFilePath();
        $this->connection = $this->getConnection();
    }

    /**
     * Returns the connections to use for storing plantext ballots
     * @return \Illuminate\Database\SQLiteConnection
     * @throws \Exception
     */
    private function getConnection(): SQLiteConnection
    {
        try {

            $folder = dirname($this->pathname);
            if (!file_exists($folder) && !is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $pdo = new PDO('sqlite:' . $this->pathname);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn = new SQLiteConnection($pdo);
            $conn->setTablePrefix('');
            $conn->setDatabaseName('');
            return $conn;
//        $builder = new \Illuminate\Database\Query\Builder($connection);
        } catch (\Throwable $e) {
            throw  new \Exception("Can't open database @ $this->pathname");
        }
    }

    // ###############################################################################################

    /**
     * Returns the name of the table to use in
     * @param \App\Models\Election $election
     * @return string
     * @see \App\Models\Election::getTallyDatabase()
     */
    public static function getOutputTableName(Election $election): string
    {
        return 'e_' . $election->id;
    }

    /**
     * @param \App\Models\Question $question
     * @return string
     */
    public static function getQuestionAnswersTableName(Question $question): string
    {
        return "e_{$question->election_id}_q_{$question->local_id}_a";
    }

    /**
     * @param \App\Models\Question $question
     * @return array
     */
    public static function getAnswerColumnNames(Question $question): array
    {
        $names = [];
        for ($aIdx = 0; $aIdx < $question->max; $aIdx++) {
            $a = $aIdx + 1;
            $names[] = "q_{$question->local_id}_a_{$a}";
        }
        return $names;
    }

    // ###############################################################################################

    /**
     * Creates a sqlite database with plaintexts ballots
     */
    public function setupOutputTables(): bool
    {
        Log::debug('setupOutputTables > ' . $this->election->getOutputDatabaseStorageFilePath());

        // create a table for each question
        Log::debug('Creating a table for each question');
        foreach ($this->election->questions as $question) {
            try {
                $question->question_type->getClass()::createAnswersTable($this, $question);
            } catch (Throwable $e) {
                Log::error('Error during question answers table creation');
                $question_answers_table_name = TallyDatabase::getQuestionAnswersTableName($question);
                Log::debug($question_answers_table_name);
                return false;
            }
        }

        // create a table for all ballots
        $output_table_name = TallyDatabase::getOutputTableName($this->election);

        Log::debug("Dropping (if exists) and creating table $output_table_name");
        $this->connection->getSchemaBuilder()->dropIfExists($output_table_name);
        $this->connection->getSchemaBuilder()->create($output_table_name, function (Blueprint $table) {

            $table->increments('id');

            foreach ($this->election->questions as $question) {
                $question_answers_table_name = "e_{$this->election->id}_q_{$question->local_id}_a";
                foreach (TallyDatabase::getAnswerColumnNames($question) as $cName) {
                    $table->unsignedInteger($cName)->nullable();
                    $table->foreign($cName)->references('id')->on($question_answers_table_name);
                }
            }

        });

        // create views with queries from questions
        Log::debug('Creating tally view');
        foreach ($this->election->questions as $question) {
            $viewName = "tally_q_{$question->local_id}";
            $query = "CREATE VIEW $viewName AS " . $question->question_type->getClass()::getTallyQuery($question);
            try {
                $this->connection->statement("DROP VIEW IF EXISTS $viewName;");
                $this->connection->statement($query);
            } catch (Throwable $e) {
                Log::error('Error during view creation');
                Log::debug($query);
                return false;
            }
        }

        return true;

    }

    /**
     *
     */
    public function tally(): void
    {
        foreach ($this->election->questions as $question) {
            $query = $question->question_type->getClass()::getTallyQuery($question);
            $results = $this->connection->select(DB::raw($query));
            $question->tally_result = $results;
            $question->save();
        }
    }

    // ###############################################################################################

    /**
     * Returns a record ready for insertion
     * @param array $plainVote
     * @return array
     * @throws \Exception
     */
    private function getBallotRecord(array $plainVote): array
    {
        $record = [];

        //set all as null
        foreach ($this->election->questions as $question) {
            foreach (TallyDatabase::getAnswerColumnNames($question) as $cName) {
                $record[$cName] = null;
            }
        }

        // fill
        foreach ($plainVote as $questionIdx => $questionAnswers) {
            $q = $questionIdx + 1;
            foreach ($questionAnswers as $answerIdx => $questionAnswer) {
                $a = $answerIdx + 1;
                $fieldName = "q_{$q}_a_{$a}";
                if (!array_key_exists($fieldName, $record)) {
                    throw new Exception("$fieldName is not present in [" . implode(',', array_keys($record)) . ']');
                }
                $record[$fieldName] = $questionAnswer;
            }
        }

//        sort($record);

        return $record;

    }

    /**
     * @param \App\Voting\CryptoSystems\Plaintext[] $plainTextVotes
     * @return bool
     */
    public function insertPlainTextBallots(array $plainTextVotes): bool
    {

        // remove existing records
        try {
            $this->connection->table(TallyDatabase::getOutputTableName($this->election))->truncate();
        } catch (Throwable $e) {
            Log::error('insertPlainTextBallots > Error during table truncation');
            Log::error($e->getMessage());
            return false;
        }

        $successCount = 0;
        $questionCount = $this->election->questions->count();

        foreach ($plainTextVotes as $plainTextVote) {
            $plainVoteArray = null;
            try {
                $plainVoteArray = Small_JSONBallotEncoding::decode($plainTextVote); // TODO generalize
                if ($this->insertBallot($plainVoteArray, $questionCount)) {
                    $successCount++;
                }
            } catch (Throwable $e) {
                Log::error('insertPlainTextBallots > Error during plaintext decoding and insertion');
                Log::error($e->getMessage());
                Log::debug($plainTextVote->toString());
                Log::debug($plainVoteArray);
            }

        }

        $failCount = count($plainTextVotes) - $successCount;

        Log::info("DONE! $successCount succesful insertions, $failCount failed insertions");

        return $failCount === 0;
    }

    /**
     * Inserts a decoded plaintext (array) into the output DATABASE
     * @param array $plainVote structure extracted from JSON
     * @param int|null $questionCount
     * @return bool
     * @throws \Exception
     */
    public function insertBallot(array &$plainVote, ?int $questionCount = null): bool
    {
        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

//        Log::debug($plainVote);

        $questionCount = $questionCount ?? $this->election->questions->count();

        if (!is_array($plainVote) || count($plainVote) !== $questionCount) {
            Log::error('Ignoring vote due to wrong lenght');
            /** @noinspection PhpParamsInspection */
            Log::debug($plainVote);
            return false;
        }

        $this->connection->flushQueryLog();
        $this->connection->enableQueryLog();

        $record = $this->getBallotRecord($plainVote);

        try {
            return $this->connection->table(TallyDatabase::getOutputTableName($this->election))->insert($record);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            Log::debug($this->connection->getQueryLog());
            Log::debug($record);
        }

        $this->connection->disableQueryLog();
        return false;

    }

    // ###############################################################################################

    /**
     * @return int
     */
    public function getRecordCount(): int
    {
        return $this->connection->table(TallyDatabase::getOutputTableName($this->election))->count();
    }

    // ###############################################################################################

    /**
     *
     */
    public function delete(): void
    {
        unlink($this->pathname);
    }

    /**
     * @return bool
     */
    public function file_exists(): bool
    {
        return file_exists($this->pathname);
    }

}
