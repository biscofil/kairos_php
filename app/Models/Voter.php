<?php

namespace App\Models;

use App\Models\Cast\PublicKeyCaster;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Voter
 * @package App\Models
 * @property int id
 *
 * @property int election_id
 * @property Election election
 *
 * @property int user_id
 * @property User user
 *
 * @property int|null last_vote_cast_id
 * @property CastVote|null lastVoteCast
 *
 * @property null|\App\Voting\CryptoSystems\PublicKey public_key
 * @property null|string secret_key
 *
 * @method static self make()
 * @method static self findOrFail($id)
 */
class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'election_id',
        'user_id',
        'last_vote_cast_id',
        'secret_key', 'public_key'
    ];

    protected $casts = [
        'public_key' => PublicKeyCaster::class
    ];

    // ############################################# RELATIONS

    /**
     * @return BelongsTo|\App\Models\Election
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * @return BelongsTo|\App\Models\User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany|\App\Models\CastVote
     */
    public function votes(): HasMany
    {
        return $this->hasMany(CastVote::class, 'voter_id');
    }

    /**
     * @return BelongsTo|\App\Models\CastVote
     */
    public function lastVoteCast(): BelongsTo
    {
        return $this->belongsTo(CastVote::class, 'last_vote_cast_id');
    }

    // #############################################

}
