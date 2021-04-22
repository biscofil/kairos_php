<?php

namespace App\Models;

use App\Models\Cast\BigIntCaster;
use App\Models\Cast\ModelWithFieldsWithParameterSets;
use App\Models\Cast\POKCaster;
use App\Models\Cast\PrivateKeyCasterCryptosystem;
use App\Models\Cast\PublicKeyCasterCryptosystem;
use App\Models\Cast\ThresholdBroadcastCasterCryptosystem;
use App\Voting\CryptoSystems\ElGamal\DLogProof;
use App\Voting\CryptoSystems\PublicKey;
use App\Voting\CryptoSystems\SecretKey;
use App\Voting\CryptoSystems\ThresholdBroadcast;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use phpseclib3\Math\BigInteger;

/**
 * Class Trustee
 * @package App\Models
 * @property int $id
 * @property string uuid
 * @property null|PublicKey public_key
 * @property null|SecretKey private_key
 * @property null|DLogProof pok // TODO
 * @property null|string public_key_hash
 * @property null|bool qualified
 *
 * @property ThresholdBroadcast|null broadcast
 * @property BigInteger|null share_sent
 * @property BigInteger|null share_received
 *
 * @property null|int user_id
 * @property null|User user
 *
 * @property null|int peer_server_id
 * @property null|PeerServer peerServer
 *
 * @method static self make()
 * @method static self|Builder peerServers()
 * @method static self findOrFail($id)
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
        //
        'election_id',
        'public_key',
        'private_key',
        'pok',
        'public_key_hash',
        'qualified',
        'broadcast',
        //
        'share_sent',
        'share_received'
    ];

    public $shareableFields = [
        'uuid'
    ];

    protected $casts = [
        'public_key' => PublicKeyCasterCryptosystem::class,
        'private_key' => PrivateKeyCasterCryptosystem::class,
        'pok' => POKCaster::class,
        'qualified' => 'bool',
        'broadcast' => ThresholdBroadcastCasterCryptosystem::class,
        'share_sent' => BigIntCaster::class,
        'share_received' => BigIntCaster::class
    ];

    protected $hidden = [
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

    // ################################################################
    // ############################################ Scopes
    // ################################################################

    /**
     * Filters trustees that have a peer server
     * @param Builder $builder
     * @return Builder
     */
    public function scopePeerServers(Builder $builder): Builder
    {
        return $builder->whereNotNull('peer_server_id');
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

}
