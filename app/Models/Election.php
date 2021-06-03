<?php

namespace App\Models;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Jobs\OnElectionFreezeTimeout;
use App\Jobs\SendP2PMessage;
use App\Models\Cast\ModelWithFieldsWithParameterSets;
use App\Models\Cast\PublicKeyCaster;
use App\Models\Cast\SecretKeyCaster;
use App\P2P\Messages\Freeze\Freeze1IAmFreezingElection\Freeze1IAmFreezingElectionRequest;
use App\P2P\Messages\WillYouBeAElectionTrusteeForMyElection\WillYouBeAElectionTrusteeForMyElectionRequest;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\SecretKey;
use App\Voting\QuestionTypes\MultipleChoice;
use Carbon\Carbon;
use Database\Factories\ElectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;

/**
 * Class Election
 * @package App\Models
 * @property int id
 * @property string uuid
 * @property string slug
 * @property string name
 * @property string description
 * @property string help_email
 * @property string info_url
 *
 * @property bool is_private
 * @property bool is_featured
 * @property \App\Models\Question[]|Collection questions
 *
 * @property int peer_server_id ID of the server that created the election
 * @property PeerServer peerServerAuthor Server that created the election
 *
 * @property int|null admin_id
 * @property User|null admin
 *
 * @property int|null min_peer_count_t
 * @property CryptoSystemEnum cryptosystem
 * @property \App\Enums\AnonymizationMethodEnum anonymization_method
 *
 * @property null|PublicKey public_key
 * @property null|SecretKey private_key
 *
 * @property null|Carbon frozen_at
 * @property null|Carbon archived_at
 *
 * @property Carbon voting_starts_at
 * @property null|Carbon voting_started_at
 * @property Carbon voting_ends_at
 * @property null|Carbon voting_ended_at
 *
 * @property null|Carbon tallying_started_at
 * @property null|Carbon tallying_finished_at
 *
 * @property Collection|Trustee[] trustees
 * @property Collection|\App\Models\PeerServer[] peerServers
 *
 * @property-read array issues
 *
 * @property bool use_voter_alias
 * @property bool use_advanced_audit_features
 * @property bool randomize_answer_order
 * @property CastVote[]|Collection votes
 * @property-read bool has_system_trustee
 *
 * @property \App\Models\Mix[] mixes
 *
 * @method static self create(array $data)
 * @method static self make(array $data)
 * @method static self|null find($id)
 * @method static self findOrFail($id)
 * @method static self|Builder featured()
 * @method static self|Builder ofThisServer()
 * @method static first()
 *
 * @method static ElectionFactory factory()
 */
class Election extends Model
{
    use HasShareableFields;
    use HasFactory;
    use ModelWithFieldsWithParameterSets;

    protected $fillable = [
        'uuid',
        'slug',
        //
        'peer_server_id',
        //
        'name',
        'description',
        'help_email',
        'info_url',
        'is_private',
        'is_featured',
        //
        'cryptosystem',
        'anonymization_method',
        'public_key', 'private_key',
        'min_peer_count_t',
        //
        'is_registration_open', // TODO
        'use_voter_alias',
        'use_advanced_audit_features',
        'randomize_answer_order',
        //
        'registration_starts_at',
        'voting_starts_at',
        'voting_started_at',
        'voting_extended_until',
        'voting_ends_at',
        'voting_ended_at',
        //
        'tallying_started_at',
        'tallying_finished_at',
        'tallying_combined_at',
        'results_released_at',
        //
        'frozen_at',
        'archived_at',
    ];

    public $shareableFields = [
        'uuid',
        'slug',
        //
        'name',
        'description',
        'help_email',
        'info_url',
        'is_private',
        'is_featured',
        //
        'cryptosystem',
        'anonymization_method',
        'min_peer_count_t',
        //
        'is_registration_open', // TODO
        'use_voter_alias',
        'use_advanced_audit_features',
        'randomize_answer_order',
        //
        'registration_starts_at',
        'voting_extended_until',
    ];

    protected $casts = [
        'id' => 'int',
        //
        'min_peer_count_t' => 'int',
        'anonymization_method' => AnonymizationMethodEnum::class,
        'cryptosystem' => CryptoSystemEnum::class,
        'public_key' => PublicKeyCaster::class,
        'private_key' => SecretKeyCaster::class,
        //
        'is_private' => 'bool',
        'is_featured' => 'bool',
        'use_voter_alias' => 'bool',
        'use_advanced_audit_features' => 'bool',
        'randomize_answer_order' => 'bool',
        //
        'registration_starts_at' => 'datetime',
        'voting_starts_at' => 'datetime',
        'voting_started_at' => 'datetime',
        'voting_extended_until' => 'datetime',
        'voting_ends_at' => 'datetime',
        'voting_ended_at' => 'datetime',
        //
        'tallying_started_at' => 'datetime',
        'tallying_finished_at' => 'datetime',
        'tallying_combined_at' => 'datetime',
        'results_released_at' => 'datetime',
        //
        'frozen_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    protected $appends = [
        'is_auth_user_admin',
        'is_auth_user_trustee',
        'trustee_count',
        'voter_count',
        'cast_votes_count',
        'admin_name',
        'issues',
        'current_phase',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // ############################################ Attributes ############################################

    /**
     * If the user is logged in, it returns a bool that indicates if the user is the creator of the election,
     * null if not logged
     * @return bool|null
     * @noinspection PhpUnused
     */
    public function getIsAuthUserAdminAttribute(): ?bool
    {
        if (isLogged()) {
            return getAuthUser()->id == $this->admin_id;
        }
        return null;
    }

    /**
     * If the user is logged in, it returns a bool that indicates if the user is a trustee of the election,
     * null if not logged
     * @return bool|null
     * @noinspection PhpUnused
     */
    public function getIsAuthUserTrusteeAttribute(): ?bool
    {
        return !is_null($this->getAuthTrustee());
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getTrusteeCountAttribute(): int
    {
        return $this->trustees()->count();
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getVoterCountAttribute(): int
    {
        return 0; // TODO
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getCastVotesCountAttribute(): int
    {
        return 0; // TODO
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAdminNameAttribute(): ?string
    {
        if (is_null($this->admin_id)) {
            return null;
        }
        return $this->admin->name;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getIssuesAttribute(): array
    {
        $issues = [];

        if ($this->questions()->count() == 0) {
            $issues[] = [
                'type' => 'questions',
                'action' => 'Add questions to the ballot'
            ];
        }

        if ($this->trustees()->peerServersAcceptingBallots()->count() == 0) {
            $issues[] = [
                'type' => 'trustees',
                'action' => 'Add at least one peer server trustee that accepts ballots'
            ];
        }

        if ($this->hasTLThresholdScheme()) {
            //t-l
            if ($this->trustees()->users()->count()) {
                $issues[] = [
                    'type' => 'trustees',
                    'action' => "User trustees can't be specified when using a T-L-threshold"
                ];
            }
        }

        // make sure that user trustees have uploaded their public key
        // peer servers will share theirs with the p2p protocol
        foreach ($this->trustees()->users()->get() as $userTrustee) {
            if (is_null($userTrustee->public_key)) {
                $issues[] = [
                    'type' => 'trustee keypairs',
                    'action' => 'have trustee # ' . $userTrustee->id . ' generate a keypair'
                ];
            }
        }


//        if ($this->voters()->count() == 0) { // TODO and not self.reg = open:
//            $issues[] = [
//                'type' => 'voters',
//                'action' => 'enter your voter list (or open registration to the public)'
//            ];
//        }

        return $issues;
    }

    /**
     * @return null|array
     */
    public function getCurrentPhaseAttribute(): ?array
    {
        if (is_null($this->frozen_at)) {
            return ['name' => 'Not frozen yet', 'class' => 'danger'];
        } elseif (is_null($this->voting_started_at)) {
            return ['name' => 'Waiting for scheduled opening', 'class' => 'warning'];
        } elseif (is_null($this->voting_ended_at)) {
            return ['name' => 'Voting phase. Waiting for scheduled closing', 'class' => 'success'];
        } else {
            return ['name' => 'Voting phase ended. Waiting for anonymization and tally', 'class' => 'info'];
        }
    }

    // ############################################ Scopes ############################################

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function scopeFeatured(Builder $builder): Builder
    {
        return $builder->where('is_featured', '=', 1);
    }

    /**
     * @param Builder $builder
     * @return Builder
     * @noinspection PhpUnused
     */
    public function scopeOfThisServer(Builder $builder): Builder
    {
        return $builder->where('peer_server_id', '=', PeerServer::meID);
    }

    // ############################################ Relations ############################################

    /**
     * @return HasMany|\App\Models\Question
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'election_id');
    }

    /**
     * @return BelongsTo|User
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * @return HasMany|Trustee
     */
    public function trustees(): HasMany
    {
        return $this->hasMany(Trustee::class, 'election_id');
    }

    /**
     * Returns Peer servers who act as trustees
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough|\App\Models\PeerServer
     */
    public function peerServers(): HasManyThrough
    {
        return $this->hasManyThrough(
            PeerServer::class, Trustee::class,
            'election_id', 'id',
            null, 'peer_server_id')
            ->whereNotNull('trustees.peer_server_id');
    }

    /**
     * @return HasMany|Voter
     */
    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class, 'election_id');
    }

    /**
     * TODO check, peers don't have voters
     * @return HasMany|CastVote
     */
    public function votes(): HasMany
    {
        return $this->hasMany(CastVote::class, 'election_id');
    }

    /**
     * Returns the peer server who sent us the election
     * @return BelongsTo|PeerServer
     */
    public function peerServerAuthor(): BelongsTo
    {
        return $this->belongsTo(PeerServer::class, 'peer_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough|\App\Models\Mix[]
     */
    public function mixes(): HasManyThrough
    {
        return $this->hasManyThrough(Mix::class, Trustee::class);
    }

    // ############################################

    /**
     * Returns the Trustee corresponding to the auth user
     * @return Trustee|null
     */
    public function getAuthTrustee(): ?Trustee
    {
        if (!isLogged()) {
            return null;
        }
        return $this->trustees()
            ->where('user_id', '=', getAuthUser()->id)
            ->first();
    }

    /**
     * @param User $user
     * @return Trustee
     */
    public function createUserTrustee(User $user): Trustee
    {
        $trustee = Trustee::make();
        $trustee->uuid = (string)Str::uuid();
        $trustee->user()->associate($user);
        $trustee->election()->associate($this);
        $trustee->save();
        return $trustee;
    }

    /**
     * @param PeerServer $server
     * @return Trustee
     * @throws \Exception
     */
    public function createPeerServerTrustee(PeerServer $server): Trustee
    {

        Log::debug('Creating peer server trustee for election ' . $this->id);

        $trustee = Trustee::make();
        $trustee->uuid = (string)Str::uuid();
        $trustee->peerServer()->associate($server);
        $trustee->election()->associate($this);
        $trustee->save();

        if ($server->id === PeerServer::meID) { //this server

            // if threshold and coordinator is also peer, generate key pair
            $trustee->accepts_ballots = true; // TODO remove!!!
            $trustee->generateKeyPair();
            $trustee->save();

            /**
             * @see \App\Models\Election::freeze()
             */

        } else {

            //other server
            SendP2PMessage::dispatchSync(
                new WillYouBeAElectionTrusteeForMyElectionRequest(getCurrentServer(), [$server], $this)
            );

        }

        return $trustee;
    }

    // ############################################

    /**
     * @return string
     */
    public function generateVotersHash(): string
    {
        return ''; // TODO Sort email addresses of voters and hash them
    }

    /**
     * Returns the Voter corresponding to the auth user
     * @return Voter|null
     */
    public function getAuthVoter(): ?Voter
    {
        if (!isLogged()) {
            return null;
        }
        return $this->voters()
            ->where('user_id', '=', getAuthUser()->id)
            ->first();
    }

    /**
     * @param User $user
     * @return Voter
     */
    public function createVoter(User $user): Voter
    {
        $voter = Voter::make();
        $voter->user()->associate($user);
        $voter->election()->associate($this);
        $voter->save();
        return $voter;
    }

    // ############################################ Freeze

    /**
     * @throws \Exception
     */
    public function freeze()
    {

        # generate voters hash
        // TODO $this->voter_hash = $this->generateVotersHash();

        $this->trustees->load('peerServer');

        if ($this->peerServers()->ignoreMyself()->count()) { // if there other peer servers -> P2P three phase commit for freeze

//                if ($this->election->min_peer_count_t > 0
//                    && $this->election->trustees()->where('peer_server_id', '=', PeerServer::meID)->count()) {
//                    // generate polynomial
//                    $meTrustee = $this->election->trustees()->where('peer_server_id', '=', PeerServer::meID)
//                        ->firstOrFail();
//
//                    $keyPair = $this->election->cryptosystem->getCryptoSystemClass()::generateKeypair();
//                    $meTrustee->private_key = $keyPair->sk;
//                    $meTrustee->public_key = $keyPair->pk;
//
//                    $polynomial = $keyPair->sk->getThresholdPolynomial($this->election->min_peer_count_t);
//                    $meTrustee->polynomial = $polynomial; // save my polynomial
//
//                    $meTrustee->save();
//                    // TODO move outside of P2P message class
//                }

            $this->setAsFreezing();

            /**
             * TODO merge code with
             * @see Freeze1IAmFreezingElectionRequest::onRequestReceived()
             * as they perforem the same operation
             */

            /** @var \App\Models\Trustee $meTrustee */
            $meTrustee = $this->getTrusteeFromPeerServer(getCurrentServer());

            if ($meTrustee) {

                /**
                 * keypair of current server is generated in
                 * @see \App\Models\Election::createPeerServerTrustee()
                 */

                if ($this->hasTLThresholdScheme()) {
                    // if threshold and coordinator prepare broadcast and share
                    $meTrustee->polynomial = $meTrustee->private_key->getThresholdPolynomial($this->min_peer_count_t);
                    $meTrustee->broadcast = $meTrustee->polynomial->getBroadcast();

                    // store the share of my own secret key
                    $meIdx = $meTrustee->getPeerServerIndex();
                    $meTrustee->share_received = $meTrustee->polynomial->getShare($meIdx + 1);
                }
                $meTrustee->save();
            }

            // foreach peers generate share, store it and read it in message
            $messagesToSend = $this->peerServers()->ignoreMyself()->get()
                ->map(function (PeerServer $trusteePeerServer) use ($meTrustee) {

                    $share = null;

                    if ($meTrustee && $this->hasTLThresholdScheme()) {
                        $trusteeI = $this->getTrusteeFromPeerServer($trusteePeerServer);
                        // if threshold and coordinator is also peer, send broadcast and share
                        // if coordinator is also peer -> send broadcast and share
                        $j = $trusteeI->getPeerServerIndex();
                        $trusteeI->share_sent = $meTrustee->polynomial->getShare($j + 1); // TODO check +1
                        $trusteeI->save();
                        $share = $trusteeI->share_sent;
                    }

                    return new Freeze1IAmFreezingElectionRequest(
                        getCurrentServer(),
                        $trusteePeerServer,
                        $this,
                        $this->questions,
                        $this->trustees,
                        $meTrustee ? $meTrustee->public_key : null,
                        $meTrustee ? $meTrustee->broadcast : null,
                        $share
                    );
                });

            if ($messagesToSend->count()) {
                SendP2PMessage::dispatch($messagesToSend->toArray());

                // wait for 10 seconds for a confirmation
                // delay : let's specify that a job should not be available for processing until 10 minutes after it has been dispatched:
                OnElectionFreezeTimeout::dispatch($this)->delay(now()->addSeconds(15));
            }


        } else { // this is the only server

            $this->actualFreeze();
        }

    }

    /**
     *
     */
    public function actualFreeze(): void
    {
        // Elgamal: generate combined public key, RSA: nothing
        $this->cryptosystem->getClass()::onElectionFreeze($this);
        $this->frozen_at = now();
        $this->save();
        // TODO $this->setupOutputTables();
    }

    /**
     * Returns the connections to use for storing plantext ballots
     * @return \Illuminate\Database\SQLiteConnection
     */
    public function getOutputConnection(): SQLiteConnection
    {
        $pathname = storage_path('election_' . $this->id . '.sqlite');
        $pdo = new PDO('sqlite:' . $pathname);
        $conn = new SQLiteConnection($pdo);
        $conn->setTablePrefix('');
        $conn->setDatabaseName('');
        return $conn;
//        $builder = new \Illuminate\Database\Query\Builder($connection);
    }

    /**
     * Returns the name of the table to use in
     * @return string
     * @see \App\Models\Election::getOutputConnection()
     */
    public function getOutputTableName(): string
    {
        return 'e_' . $this->id;
    }

    /**
     * Creates a sqlite database with plaintexts ballots
     */
    public function setupOutputTables()
    {
        Log::debug('setupOutputTables > ' . storage_path('election_' . $this->id . '.sqlite'));
        $connection = $this->getOutputConnection();

        // create a table for each question
        foreach ($this->questions as $idx => $question) {
            $q = $idx + 1;
            $question_answers_table_name = "e_{$this->id}_q_{$q}_a";
            $connection->getSchemaBuilder()->dropIfExists($question_answers_table_name);

            $connection->getSchemaBuilder()->create($question_answers_table_name, function (Blueprint $table) {
                $table->increments('id');

                $table->string('answer');
            });

            foreach ($question->answers as $answer) {
                $qID = $connection->table($question_answers_table_name)->insertGetId([
                    'answer' => $answer['answer']
                ]);
            }
        }

        // create a table for all ballots
        $output_table_name = $this->getOutputTableName();

        $connection->getSchemaBuilder()->dropIfExists($output_table_name);
        $connection->getSchemaBuilder()->create($output_table_name, function (Blueprint $table) {

            $table->increments('id');

            foreach ($this->questions as $idx => $question) {
                $q = $idx + 1;
                $question_answers_table_name = "e_{$this->id}_q_{$q}_a";
                foreach ($question->getAnswerColumnNames($q) as $cName) {
                    $table->unsignedInteger($cName)->nullable();
                    $table->foreign($cName)->references('id')->on($question_answers_table_name);
                }
            }

//            $table->timestamps();

        });

//        foreach ($this->votes as $vote) {
//            DB::table($output_table_name)->insert([
//                'question_1' => $qID // TODO
//            ]);
//        }
    }

    /**
     * @param bool $featured
     */
    public function setFeatured(bool $featured): void
    {
        $this->is_featured = $featured;
    }

    /**
     * @param bool $archived
     */
    public function setArchived(bool $archived): void
    {
        $this->archived_at = $archived ? now() : null;
    }

    /**
     * @return Election
     */
    public function duplicate(): Election
    {
        $e = new Election();
        $e->uuid = (string)Str::uuid();
        $e->admin()->associate(getAuthUser());

        $e->name = 'Copy of ' . $this->name;
        $e->slug = $this->slug . '_copy';
        $e->peer_server_id = PeerServer::meID;

        $e->cryptosystem = $this->cryptosystem;
        $e->anonymization_method = $this->anonymization_method;

        $e->min_peer_count_t = $this->min_peer_count_t;

//        $e->questions = $this->questions; TODO
        $e->description = $this->description;
        $e->help_email = $this->help_email;
        $e->info_url = $this->info_url;
        $e->use_voter_alias = $this->use_voter_alias;
        $e->use_advanced_audit_features = $this->use_advanced_audit_features;
        $e->randomize_answer_order = $this->randomize_answer_order;

        $e->save();
        return $e;
    }

    // ############################################

    /**
     * @param PeerServer $server
     * @param bool $fail
     * @return Trustee|null
     */
    public function getTrusteeFromPeerServer(PeerServer $server, bool $fail = false): ?Trustee
    {
        $query = $this->trustees()->where('peer_server_id', '=', $server->id);
        if ($fail) {
            return $query->firstOrFail();
        }
        return $query->first();
    }

    /**
     * @param string $uuid
     * @return Election|null
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public static function findFromUuid(string $uuid): ?Election
    {
        return self::query()->where('uuid', '=', $uuid)->first();
    }

    /**
     * Returns TRUE if this election has a L-L threshold scheme (all peers required)
     * @return bool
     */
    public function hasLLThresholdScheme(): bool
    {
        return $this->min_peer_count_t === $this->peerServers()->count();
    }

    /**
     * Returns TRUE if this election has a T-L threshold scheme (some peers required)
     * @return bool
     */
    public function hasTLThresholdScheme(): bool
    {
        return $this->min_peer_count_t < $this->peerServers()->count();
    }

    /**
     * @return bool
     */
    public function closeVotingPhase(): bool
    {
        $this->voting_ended_at = Carbon::now();
        if (!$this->save()) {
            return false;
        }
        $this->anonymization_method->getClass()::afterVotingPhaseEnds($this);
        return true;
    }

    // #######################################################################################

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getElectionPeerServerDomains(): Collection
    {
        return $this->peerServers()->get(['domain']);
    }

    /**
     * @param \Illuminate\Support\Collection|null $peerServers
     * @return array [domain => index]
     */
    public function getPeerServerIndexMapping(?Collection $peerServers = null): array
    {
        if (is_null($peerServers)) {
            $peerServers = $this->getElectionPeerServerDomains();
        }
        return $peerServers->pluck('domain')->sort()->flip()->toArray();
    }

    /**
     * @param int $idx
     * @param \Illuminate\Support\Collection|null $peerServers
     * @return \App\Models\PeerServer
     */
    public function getPeerServerFromIndex(int $idx, ?Collection $peerServers = null): PeerServer
    {
        $sortedDomains = $this->getPeerServerIndexMapping($peerServers); // [domain => index]
        $sortedDomains = array_flip($sortedDomains); // [index => domain]
        return PeerServer::withDomain($sortedDomains[$idx])->firstOrFail();
    }

    /**
     * @param int $id
     * @param \Illuminate\Support\Collection|null $peerServers
     * @return int
     */
    public function getIndexAfter(int $id, ?Collection $peerServers = null): int
    {
        $sortedDomains = $this->getPeerServerIndexMapping($peerServers); // [domain => index]
        return ($id + 1) % count($sortedDomains);
    }

    // #######################################################################################

    /**
     *
     */
    public function tally(): void
    {
        Log::info("Running tally of election $this->id");

        $this->tallying_started_at = Carbon::now();

        $conn = $this->getOutputConnection();

        foreach ($this->questions as $idx => $question) {
            $query = MultipleChoice::getTallyQuery($question, $idx + 1);
            $results = $conn->select(DB::raw($query));

            $question->tally_result = $results;
            $question->save();
        }
        $this->tallying_finished_at = Carbon::now();
        $this->save();

        Log::info('Ballots tallied');

    }

}
