export default class Answer {

    /**
     *
     * @param answer : String
     * @param url : String
     * @param local_id : Number
     */
    constructor(answer, url, local_id) {
        this.answer = answer;
        this.url = url;
        this.local_id = local_id;
    }

    /**
     *
     * @param d
     * @return {Answer}
     */
    static fromJSONObject(d) {
        return new Answer(
            d.answer,
            d.url,
            d.local_id
        );
    }

}
