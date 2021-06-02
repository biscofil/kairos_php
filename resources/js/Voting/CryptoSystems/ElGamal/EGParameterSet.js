import EGPublicKey from "./EGPublicKey";
import EGSecretKey from "./EGSecretKey";
import {modPow, randBetween} from "bigint-crypto-utils";

export default class EGParameterSet {

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
        let pk = new EGPublicKey(this, y);
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
     * @param d : Object
     * @returns {EGParameterSet}
     */
    static fromJSONObject(d) {
        return new EGParameterSet(
            BigInt("0x" + d.p),
            BigInt("0x" + d.q),
            BigInt("0x" + d.g)
        );
    };

    /**
     *
     * @param other : EGParameterSet
     * @return {boolean}
     */
    equals(other) {
        return this.p === other.p
            && this.q === other.q
            && this.g === other.g
    }
}
