export default class PeerServer {

    /**
     *
     * @param id : Number
     * @param ip : String
     * @param name : String
     */
    constructor(id, ip, name) {
        this.id = id;
        this.ip = ip;
        this.name = name;
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
            d.name
        );
    };

}
