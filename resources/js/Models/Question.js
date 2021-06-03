import Answer from "./Answer";

export default class Question {

    /**
     *
     * @param id : Number
     * @param answers : Answer[]
     * @param min : Number
     * @param max : Number
     */
    constructor(id, answers, min, max) {
        this.id = id;
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
            Number(d.id),
            d.answers.map(a => {
                return Answer.fromJSONObject(a);
            }),
            Number(d.min),
            Number(d.max)
        );
    }


}
