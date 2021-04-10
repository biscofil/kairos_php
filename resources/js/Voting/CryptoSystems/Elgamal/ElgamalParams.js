import EGPublicKey from "./EGPublicKey";
import EGSecretKey from "./EGSecretKey";
import {modPow, randBetween} from "bigint-crypto-utils";

export default class ElgamalParams {

    /**
     * @param p : BigInt
     * @param q : BigInt
     * @param g : BigInt
     */
    constructor(p, q, g) {
        this.p = p;
        this.q = q;
        this.g = g;
    }

    /**
     *
     * @return {EGSecretKey}
     */
    generate() {
        // get the value x
        let x = randBetween(this.q);
        let y = modPow(this.g, x, this.p);
        let pk = new EGPublicKey(this.p, this.q, this.g, y);
        return new EGSecretKey(x, pk);
    }

    /**
     *
     * @return {{p: string, q: string, g: string}}
     */
    toJSONObject() {
        return {
            g: this.g.toString(),
            p: this.p.toString(),
            q: this.q.toString()
        };
    }

    /**
     * @param pk : EGPublicKey
     * @return {ElgamalParams}
     */
    static fromPublicKey(pk){
        return new ElgamalParams(
            BigInt(pk.p),
            BigInt(pk.q),
            BigInt(pk.g)
        );
    }

    /**
     * @returns {ElgamalParams}
     */
    static fromJSONObject(d) {
        return new ElgamalParams(
            BigInt(d.p),
            BigInt(d.q),
            BigInt(d.g)
        );
    };
}
