<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Mix
 * @package App\Models
 * @property int id
 * @property int round
 * @property string hash
 *
 * @property int|null previous_mix_id
 * @property \App\Models\Mix|null previousMix
 *
 * @property int trustee_id
 * @property \App\Models\Trustee trustee
 */
class Mix extends Model
{
    use HasShareableFields;
    use HasFactory;

    protected $fillable = [
        'round',
        'previous_mix_id',
        'hash',
        'trustee_id'
    ];

    public $shareableFields = [
        'round',
        'hash',
    ];

    // ########################################## RELATIONS

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Mix
     * @noinspection PhpUnused
     */
    public function previousMix(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_mix_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Trustee
     * @noinspection PhpUnused
     */
    public function trustee(): BelongsTo
    {
        return $this->belongsTo(Trustee::class, 'trustee_id');
    }

    // ########################################## RELATIONS

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return 'election_' . $this->trustee->election->uuid . '_mix_' . $this->id;
    }

    public function downloadFromTrustee()
    {
        // TODO
    }


}
