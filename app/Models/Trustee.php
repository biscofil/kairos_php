<?php

namespace App\Models;

use App\Crypto\DLogProof;
use App\Crypto\EGPrivateKey;
use App\Crypto\EGPublicKey;
use App\Models\Cast\EGPrivateKeyCaster;
use App\Models\Cast\EGPublicKeyCaster;
use App\Models\Cast\ModelWithCryptoFields;
use App\Models\Cast\POKCaster;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Trustee
 * @package App\Models
 * @property int $id
 * @property string uuid
 * @property null|EGPrivateKey private_key
 * @property null|EGPublicKey public_key
 * @property null|DLogProof pok
 * @property null|string public_key_hash
 *
 * @property null|int user_id
 * @property null|User user
 *
 * @method static self make()
 * @method static self|Builder systemTrustees()
 * @method static self findOrFail($id)
 */
class Trustee extends Model
{
    use HasFactory;
    use ModelWithCryptoFields;

    protected $fillable = [
        'uuid',
        'user_id',
        'election_id',
        'public_key',
        'private_key',
        'pok',
        'public_key_hash'
    ];

    protected $casts = [
        'public_key' => EGPublicKeyCaster::class,
        'private_key' => EGPrivateKeyCaster::class,
        'pok' => POKCaster::class,
    ];

    /**
     * @param Builder $builder
     * @return Builder
     * @noinspection PhpUnused
     */
    public function scopeSystemTrustees(Builder $builder): Builder
    {
        return $builder->whereNull('user_id');
    }

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
     * @return BelongsTo|User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo|Election
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * @param EGPublicKey $pk
     * @throws \Exception
     */
    public function setPublicKey(EGPublicKey $pk): void
    {
        $this->public_key = $pk;
        $this->computePublicKeyHash();
        $this->save();
    }

    /**
     *
     */
    public function computePublicKeyHash(): void
    {
        $this->public_key_hash = base64_encode(hash('sha256', $this->public_key->y));
    }

}
