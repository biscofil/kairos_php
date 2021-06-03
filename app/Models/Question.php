<?php

namespace App\Models;

use App\Enums\QuestionTypeEnum;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Question
 * @package App\Models
 * @property int id
 * @property string|QuestionTypeEnum question_type
 *
 * @property int min
 * @property int max
 *
 * @property int election_id
 * @property \App\Models\Election election
 *
 * @property string question
 * @property array answers
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
        'question_type', // TODO
        'min',
        'max',
        'election_id',
        'question',
        'answers',
        'tally_result',
    ];

    protected $casts = [
        'answers' => 'array',
        'tally_result' => 'array',
        'question_type' => QuestionTypeEnum::class,
    ];

    public $shareableFields = [
        'question_type',
        'min',
        'max',
        'question',
        'answers',
    ];

    /**
     * @return BelongsTo
     */
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class, 'election_id');
    }

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

}
