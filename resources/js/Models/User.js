export default class User {

    /**
     *
     * @param id : Number
     * @param email : String
     */
    constructor(id, email) {
        this.id = id;
        this.email = email;
    }

    /**
     *
     * @param d : Object
     * @return {User}
     */
    static fromJSONObject(d) {
        return new User(
            d.id,
            d.email,
        );
    };
}
