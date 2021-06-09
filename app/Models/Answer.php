<?php

namespace App\Models;

use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

}
