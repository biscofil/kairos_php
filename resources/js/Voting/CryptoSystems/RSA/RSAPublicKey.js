import NodeRSA from 'node-rsa/src/NodeRSA';
import PublicKey from "../PublicKey";

export default class RSAPublicKey extends PublicKey{

    /**
     *
     * @param v
     */
    constructor(v) {
        super();
        this.key = new NodeRSA();
        this.key.importKey(v, 'public');
        this.encrypt(21231312n);
    }

    /**
     *
     * @param d
     * @return {RSAPublicKey}
     */
    static fromJSONObject(d) {
        return new RSAPublicKey(d.v);
    }

}
