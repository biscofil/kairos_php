<?php

namespace App\Models;

use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class Answer
 * @package App\Models
 * @property int id
 * @property int local_id
 *
 * @property string answer
 * @property string|null url
 * @property array|null attributes
 * @property int question_id
 *
 * @property \App\Models\CastVote[]|Collection votes
 *
 * @method static AnswerFactory factory()
 */
class Answer extends Model
{
    use HasFactory;
    use HasShareableFields;

    protected $fillable = [
        'local_id',
        'answer',
        'url',
        'attributes',
        'question_id',
    ];

    protected $casts = [
        'attributes' => 'array'
    ];

    public $shareableFields = [
        'local_id',
        'answer',
        'url',
        'attributes',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\CastVote
     */
    public function votes(): HasMany
    {
        return $this->hasMany(CastVote::class);
    }

}
