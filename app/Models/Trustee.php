<?php

namespace App\Models;

use App\Models\Cast\BigIntCaster;
use App\Models\Cast\DLogProofCaster;
use App\Models\Cast\ModelWithFieldsWithParameterSets;
use App\Models\Cast\PublicKeyCaster;
use App\Models\Cast\SecretKeyCaster;
use App\Models\Cast\ThresholdBroadcastCaster;
use App\Models\Cast\ThresholdPolynomialCaster;
use App\Voting\CryptoSystems\ElGamal\DLogProof;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\SecretKey;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use App\Voting\CryptoSystems\ThresholdPolynomial;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use phpseclib3\Math\BigInteger;

/**
 * Class Trustee
 * @package App\Models
 * @property int $id
 * @property string uuid
 *
 * @property bool accepts_ballots
 * @property null|PublicKey public_key
 * @property null|SecretKey private_key
 * @property null|DLogProof pok // TODO
 * @property null|string public_key_hash
 *
 * @property null|bool qualified
 *
 * @property ThresholdPolynomial|null polynomial
 * @property ThresholdBroadcast|null broadcast
 *
 * @property BigInteger|null share_sent
 * @property BigInteger|null share_received
 * @property bool freeze_ready
 *
 * @property null|int user_id
 * @property null|User user
 *
 * @property null|int peer_server_id
 * @property null|PeerServer peerServer
 *
 * @property int election_id
 * @property \App\Models\Election election
 *
 * @property \App\Models\Mix[] mixes
 *
 * @method static self make()
 * @method self|Builder peerServers() Filters peer server trustees
 * @method self|Builder peerServersAcceptingBallots() Filters peer server trustees that accepts ballots
 * @method self|Builder users() Filter user trustees
 * @method static self findOrFail($id)
 * @method static self|null find(int|array $array)
 */
class Trustee extends Model
{
    use HasShareableFields;
    use HasFactory;
    use ModelWithFieldsWithParameterSets;

    protected $fillable = [
        'uuid',
        //
        'user_id', 'peer_server_id',
        'accepts_ballots',
        //
        'election_id',
        'public_key',
        'private_key',
        'pok',
        'public_key_hash',
        'qualified',
        //
        'polynomial',
        'broadcast',
        //
        'share_sent',
        'share_received',
        //
        'freeze_ready',
    ];

    public $shareableFields = [
        'uuid',
        'accepts_ballots',
    ];

    protected $casts = [
        'accepts_ballots' => 'bool',
        'public_key' => PublicKeyCaster::class,
        'private_key' => SecretKeyCaster::class,
        'pok' => DLogProofCaster::class,
        'qualified' => 'bool',
        'polynomial' => ThresholdPolynomialCaster::class,
        'broadcast' => ThresholdBroadcastCaster::class,
        'share_sent' => BigIntCaster::class,
        'share_received' => BigIntCaster::class,
        'freeze_ready' => 'bool',
    ];

    protected $hidden = [
        'polynomial',
        'private_key',
        'share_sent',
        'share_received'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * @param string $uuid
     * @return \App\Models\Trustee|null
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    public static function findUUID(string $uuid): ?Trustee
    {
        return self::query()
            ->where('uuid', '=', $uuid)
            ->first();
    }

    // ################################################################
    // ############################################ Scopes
    // ################################################################

    /**
     * Filters peer server trustees
     * @param Builder $builder
     * @return Builder
     */
    public function scopePeerServers(Builder $builder): Builder
    {
        return $builder->whereNotNull('peer_server_id');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePeerServersAcceptingBallots(Builder $builder): Builder
    {
        return $builder->whereNotNull('peer_server_id')
            ->where('accepts_ballots', '=', true);
    }

    /**
     * Filter user trustees
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsers(Builder $builder): Builder
    {
        return $builder->whereNull('peer_server_id');
    }

    // ################################################################
    // ############################################ Relations
    // ################################################################

    /**
     * @return BelongsTo|Election
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * @return BelongsTo|User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo|User
     */
    public function peerServer(): BelongsTo
    {
        return $this->belongsTo(PeerServer::class, 'peer_server_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Mix[]
     */
    public function mixes(): HasMany
    {
        return $this->hasMany(Mix::class, 'trustee_id');
    }

    // ################################################################
    // ################################################################
    // ################################################################

    /**
     * @param PublicKey $pk
     * @throws Exception
     */
    public function setPublicKey(PublicKey $pk): void
    {
        $this->public_key = $pk;
        $this->computePublicKeyHash();
        $this->save();
    }

    /**
     * computes the has of the public key and stores it
     * does not save to DB
     */
    public function computePublicKeyHash(): void
    {
        $this->public_key_hash = $this->public_key->getFingerprint();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function _getElectionPeerServers(): Collection
    {
        return $this->election->peerServers()->get(['domain']);
    }

    /**
     * Returns an integer >= 0 indicating the index of the peer server
     * 0 => first
     * @param \Illuminate\Support\Collection|null $peerServers
     * @return int
     * @throws \Exception
     */
    public function getPeerServerIndex(?Collection $peerServers = null): int
    {
        if (is_null($this->peer_server_id)) {
            throw new \Exception('getIndex can be called only on peer server trustees');
        }
        if (is_null($peerServers)) {
            $peerServers = $this->_getElectionPeerServers();
        }
        $sortedDomains = $peerServers->pluck('domain')->flip()->toArray();
        return $sortedDomains[$this->peerServer->domain];
    }

    /**
     * @param \App\Models\Trustee $trustee
     * @param \Illuminate\Support\Collection|null $peerServers
     * @return bool
     * @throws \Exception
     */
    public function comesAfterTrustee(Trustee $trustee, ?Collection $peerServers = null): bool
    {
        if (is_null($peerServers)) {
            $peerServers = $this->_getElectionPeerServers();
        }
        // check if ( ID1 + 1 ) mod n = ID2
        return (($trustee->getPeerServerIndex($peerServers) + 1) % $peerServers->count())
            === $this->getPeerServerIndex($peerServers);
    }


    /**
     * Generates keypair of the cryptosystem used in the elections
     * @return void
     */
    public function generateKeyPair(): void
    {
        $keyPair = $this->election->cryptosystem->getClass()::getKeyPairClass()::generate();
        $this->public_key = $keyPair->pk;
        $this->computePublicKeyHash();
        $this->private_key = $keyPair->sk;
    }

}
