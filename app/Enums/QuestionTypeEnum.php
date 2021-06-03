<?php


namespace App\Enums;


use App\Voting\QuestionTypes\BelongsToQuestionType;
use App\Voting\QuestionTypes\MultipleChoice;
use App\Voting\QuestionTypes\QuestionType;
use BenSampo\Enum\Enum;

/**
 * @method static static MultipleChoice()
 */
final class  QuestionTypeEnum extends Enum implements GetSetIdentifier
{

    public const MultipleChoice = 'multiple_choice';

    public const QUESTION_TYPES = [
        self::MultipleChoice => MultipleChoice::class,
    ];

    /**
     * @param BelongsToQuestionType $obj
     * @return string
     */
    public static function getIdentifier($obj): string
    {
        $v = array_flip(self::QUESTION_TYPES); // [ MultipleChoice::class => 'multiple_choice', ... ]
        $key = $obj::getQuestionType();
        if (!array_key_exists($key, $v)) {
            throw new \RuntimeException('Unknown question type ' . $key);
        }
        return $v[$key];
    }

    /**
     * @param string $identifier
     * @return string|QuestionType
     */
    public static function getByIdentifier(string $identifier): string
    {
        if (!array_key_exists($identifier, self::QUESTION_TYPES)) {
            throw new \RuntimeException('Invalid question type ' . $identifier);
        }
        return self::QUESTION_TYPES[$identifier];
    }

    /**
     * @return string|QuestionType
     */
    public function getClass(): string
    {
        return self::getByIdentifier($this->value);
    }
}
