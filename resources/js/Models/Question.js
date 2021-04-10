import Answer from "./Answer";

export default class Question {

    /**
     *
     * @param answers : Answer[]
     * @param min : Number
     * @param max : Number
     */
    constructor(answers, min, max) {
        this.answers = answers;
        this.min = min;
        this.max = max;
    }

    /**
     *
     * @param d
     * @return {Question}
     */
    static fromJSONObject(d) {
        return new Question(
            d.answers.map(a => {
                return Answer.fromJSONObject(a);
            }),
            Number(d.min),
            Number(d.max)
        );
    }


}
