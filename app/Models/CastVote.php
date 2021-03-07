<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CastVote
 * @package App\Models
 * @property int id
 * @property string vote
 * @property string ip
 * @property Carbon cast_at
 * @property string hash
 * @property int voter_id
 * @property Voter voter
 * @property Carbon|null verified_at
 * @property Carbon|null invalidated_at
 */
class CastVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'vote',
        'ip',
        'cast_at',
        'hash',
        'verified_at',
        'invalidated_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo|Voter
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class, 'voter_id');
    }

    /**
     *
     */
    public function verify(): bool
    {

        if ($this->verified_at || $this->invalidated_at) {
            return false;
        }

        // TODO copy helios/crypto/electionalgs.py/EncryptedVote@319

        // Incorrect number of answers ({n_answers}) vs questions ({n_questions})

        // Incorrect election_hash {our_election_hash} vs {actual_election_hash}

        // Incorrect election_uuid {our_election_uuid} vs {actual_election_uuid}

        // if all good, store

        $this->verified_at = now();
        return $this->save();

    }
}
