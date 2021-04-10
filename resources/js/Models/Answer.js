export default class Answer {

    /**
     *
     * @param answer : String
     * @param url : String
     */
    constructor(answer, url) {
        this.answer = answer;
        this.url = url;
    }

    /**
     *
     * @param d
     * @return {Answer}
     */
    static fromJSONObject(d) {
        return new Answer(
            d.answer,
            d.url
        );
    }

}
