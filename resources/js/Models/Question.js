import Answer from "./Answer";

export default class Question {

    /**
     *
     * @param id : Number
     * @param answers : Answer[]
     * @param min : Number
     * @param max : Number
     * @param tally_query : String
     * @param tally_result : Object
     */
    constructor(id, answers, min, max, tally_query, tally_result) {
        this.id = id;
        this.answers = answers;
        this.min = min;
        this.max = max;
        this.tally_query = tally_query;
        this.tally_result = tally_result;
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
            Number(d.max),
            d.tally_query,
            d.tally_result
        );
    }


}
