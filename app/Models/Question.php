<?php

namespace App\Models;

use App\Enums\QuestionTypeEnum;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Question
 * @package App\Models
 * @property int id
 * @property int local_id
 *
 * @property string|QuestionTypeEnum question_type
 *
 * @property int min
 * @property int max
 *
 * @property int election_id
 * @property \App\Models\Election election
 *
 * @property string question
 * @property \App\Models\Answer[]|\Illuminate\Support\Collection answers
 *
 * @property array|null tally_result
 *
 * @method static QuestionFactory factory()
 */
class Question extends Model
{

    use HasFactory;
    use HasShareableFields;

    protected $fillable = [
        'local_id',
        'question_type',
        'min',
        'max',
        'election_id',
        'question',
        'tally_result',
    ];

    protected $casts = [
        'tally_result' => 'array',
        'question_type' => QuestionTypeEnum::class,
    ];

    public $shareableFields = [
        'local_id',
        'question_type',
        'min',
        'max',
        'question',
    ];

    protected $appends = [
        'tally_query'
    ];

    // ############################################# RELATIONS

    /**
     * @return BelongsTo
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Answer
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'question_id');
    }

    // #############################################

    /**
     * @param int $q
     * @return array
     */
    public function getAnswerColumnNames(int $q): array
    {
        $names = [];
        for ($aIdx = 0; $aIdx < $this->max; $aIdx++) {
            $a = $aIdx + 1;
            $names[] = "q_{$q}_a_{$a}";
        }
        return $names;
    }

    /**
     * @noinspection PhpUnused
     */
    public function getTallyQueryAttribute(): ?string
    {
        if ($this->election_id) {
            return $this->question_type->getClass()::getTallyQuery($this, 1); // TODO question ID
        }
        return null;
    }

}
