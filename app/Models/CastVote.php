<?php

namespace App\Models;

use App\Models\Cast\CiphertextCaster;
use App\Models\Cast\ModelWithFieldsWithParameterSets;
use App\Voting\CryptoSystems\CipherText;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class CastVote
 * @package App\Models
 * @property int id
 * @property string ip
 * @property string hash
 *
 * @property int voter_id
 *
 * @property CipherText vote
 *
 * @property int verified_by
 *
 * @property int election_id
 * @property Election election
 * @property int|null answer_id
 *
 * @property Carbon|null verified_at
 * @property Carbon|null invalidated_at
 *
 * @property Carbon|null created_at
 * @property Carbon|null updated_at
 */
class CastVote extends Model
{
    use HasFactory;
    use ModelWithFieldsWithParameterSets;

    protected $fillable = [
        'vote',
        'ip',
        'hash',
        //
        'election_id',
        'answer_id',
        //
        'verified_by',
        'verified_at',
        //
        'invalidated_at',
    ];

    protected $dates = [
        'verified_at',
        'invalidated_at',
    ];

    protected $casts = [
        'vote' => CiphertextCaster::class,
    ];

    // ############################################# RELATIONS

    /**
     * @return BelongsTo|\App\Models\Voter
     */
//    public function voter(): BelongsTo
//    {
//        return $this->belongsTo(Voter::class, 'voter_id');
//    }

    /**
     * @return BelongsTo|\App\Models\Election
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    // #############################################

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

    /**
     * @param int $id
     */
    public function setVerifiedBy(int $id)
    {
        // 2^0 = 0001
        // 2^1 = 0010
        $this->verified_by = $this->verified_by | $id;

    }

}
