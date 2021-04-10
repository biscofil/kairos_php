export default class PeerServer {

    /**
     *
     * @param id : Number
     * @param ip : String
     * @param name : String
     * @param country_code : String
     */
    constructor(id, ip, name, country_code) {
        this.id = id;
        this.ip = ip;
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
            d.ip,
            d.name,
            d.country_code
        );
    };

}
