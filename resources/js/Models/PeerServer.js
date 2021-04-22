export default class PeerServer {

    /**
     *
     * @param id : Number
     * @param domain : String
     * @param name : String
     * @param country_code : String
     */
    constructor(id, domain, name, country_code) {
        this.id = id;
        this.domain = domain;
        this.name = name;
        this.country_code = country_code;
    }

    /**
     *
     * @param d : Object
     * @return {PeerServer}
     */
    static fromJSONObject(d) {
        return new PeerServer(
            d.id,
            d.domain,
            d.name,
            d.country_code
        );
    };

}
