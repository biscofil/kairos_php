<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Models\Answer;
use App\Models\Election;
use App\Models\Question;
use App\Voting\BallotEncodings\Small_JSONBallotEncoding;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;

/**
 * Class TallyDatabase
 * @package App\Voting\AnonymizationMethods\MixNets
 * @property \App\Models\Election $election
 * @property string $pathname
 * @property SQLiteConnection $connection
 */
class TallyDatabase
{

    private Election $election;
    private string $pathname;
    private SQLiteConnection $connection;

    /**
     * TallyDatabase constructor.
     * @param \App\Models\Election $election
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
     */
    private function getConnection(): SQLiteConnection
    {
        $pdo = new PDO('sqlite:' . $this->pathname);
        $conn = new SQLiteConnection($pdo);
        $conn->setTablePrefix('');
        $conn->setDatabaseName('');
        return $conn;
//        $builder = new \Illuminate\Database\Query\Builder($connection);
    }

    // ###############################################################################################

    /**
     * Returns the name of the table to use in
     * @return string
     * @see \App\Models\Election::getTallyDatabase()
     */
    public function getOutputTableName(): string
    {
        return 'e_' . $this->election->id;
    }

    /**
     * @param \App\Models\Question $question
     * @return string
     */
    public function getQuestionAnswersTableName(Question $question): string
    {
        return "e_{$this->election->id}_q_{$question->local_id}_a";
    }

    /**
     * @param \App\Models\Question $question
     * @return array
     */
    public function getAnswerColumnNames(Question $question): array
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
    public function setupOutputTables(): void
    {
        Log::debug('setupOutputTables > ' . $this->election->getOutputDatabaseStorageFilePath());

        // create a table for each question
        foreach ($this->election->questions as $question) {
            $question_answers_table_name = $this->getQuestionAnswersTableName($question);
            $this->connection->getSchemaBuilder()->dropIfExists($question_answers_table_name);

            $this->connection->getSchemaBuilder()->create($question_answers_table_name, function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->string('answer');
                $table->string('url');
            });

            $question->answers->each(function (Answer $answer) use ($question_answers_table_name) {
                $this->connection->table($question_answers_table_name)->insert([
                    'id' => $answer->local_id,
                    'answer' => $answer->answer,
                    'url' => $answer->url
                ]);
            });

        }

        // create a table for all ballots
        $output_table_name = $this->getOutputTableName();

        $this->connection->getSchemaBuilder()->dropIfExists($output_table_name);
        $this->connection->getSchemaBuilder()->create($output_table_name, function (Blueprint $table) {

            $table->increments('id');

            foreach ($this->election->questions as $question) {
                $question_answers_table_name = "e_{$this->election->id}_q_{$question->local_id}_a";
                foreach ($this->getAnswerColumnNames($question) as $cName) {
                    $table->unsignedInteger($cName)->nullable();
                    $table->foreign($cName)->references('id')->on($question_answers_table_name);
                }
            }

        });

        // create views with queries from questions
        foreach ($this->election->questions as $question) {
            $this->connection->statement("CREATE VIEW tally_q_{$question->local_id} AS " . $question->question_type->getClass()::getTallyQuery($question));
        }

    }

    /**
     *
     */
    public function tally(): void
    {
        Log::info("Running tally of election $this->election->id");

        $this->election->tallying_started_at = Carbon::now();

        foreach ($this->election->questions as $question) {
            $query = $question->question_type->getClass()::getTallyQuery($question);
            $results = $this->connection->select(DB::raw($query));
            $question->tally_result = $results;
            $question->save();
        }

        $this->election->tallying_finished_at = Carbon::now();
        $this->election->tallying_combined_at = Carbon::now();
        $this->election->results_released_at = Carbon::now();
        $this->election->save();

        Log::info('Ballots tallied');

    }

    // ###############################################################################################

    /**
     * Returns a record ready for insertion
     * @param array $plainVote
     * @return array
     */
    private function getBallotRecord(array $plainVote): array
    {
        $record = [];

        //set all as null
        foreach ($this->election->questions as $question) {
            foreach ($this->getAnswerColumnNames($question) as $cName) {
                $record[$cName] = null;
            }
        }

        // fill
        foreach ($plainVote as $questionIdx => $questionAnswers) {
            $q = $questionIdx + 1;
            foreach ($questionAnswers as $idx => $questionAnswer) {
                $a = $idx + 1;
                $record["q_{$q}_a_{$a}"] = $questionAnswer;
            }
        }

        return $record;

    }

    /**
     * @param array $cipherTexts
     * @return bool
     */
    public function insertBallots(array $cipherTexts): bool
    {

        // remove existing records
        $this->connection->table($this->getOutputTableName())->truncate();

        $successCount = 0;
        $questionCount = $this->election->questions->count();

        foreach ($cipherTexts as $cipherText) {
            $plainVoteStr = $this->election->private_key->decrypt($cipherText);
            $plainVoteArray = Small_JSONBallotEncoding::decode($plainVoteStr); // TODO generalize
            if ($this->insertBallot($plainVoteArray, $questionCount)) {
                $successCount++;
            }
        }

        $failCount = count($cipherTexts) - $successCount;

        Log::info("DONE! $successCount succesful insertions, $failCount failed insertions");

        return $failCount === 0;
    }

    /**
     * Inserts a decoded plaintext (array) into the output DATABASE
     * @param array $plainVote structure extracted from JSON
     * @param int|null $questionCount
     * @return bool
     */
    public function insertBallot(array &$plainVote, ?int $questionCount = null): bool
    {
        $this->connection->getSchemaBuilder()->enableForeignKeyConstraints();

//        Log::debug($plainVote);

        $questionCount = $questionCount ?? $this->election->questions->count();

        if (!is_array($plainVote) || count($plainVote) !== $questionCount) {
            Log::error('Ignoring vote due to wrong lenght');
            Log::debug($plainVote);
            return false;
        }

        $this->connection->flushQueryLog();
        $this->connection->enableQueryLog();

        $record = $this->getBallotRecord($plainVote);

        try {
            return $this->connection->table($this->getOutputTableName())->insert($record);
        } catch (\Throwable $e) {
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
        return $this->connection->table($this->getOutputTableName())->count();
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
