import {modPow, randBetween} from "bigint-crypto-utils";
import EGCiphertext from "./EGCiphertext";
import Utils from "../../../Models/Utils/Utils";
import Plaintext from "../Plaintext";

export default class EGPlaintext extends Plaintext {

    /**
     * we need the public key to figure out how to encode m
     * @param m : BigInt
     * @param pk : EGPublicKey
     */
    constructor(m, pk) {
        super();
        if (m == null) {
            alert('oy null m');
            return;
        }

        this.pk = pk;
        this.m = m;
    }

    /**
     * TODO check
     * @param m : BigInt
     * @return BigInt
     */
    mapIntoSubgroup(m) {
        m += 1n;
        if (modPow(m, this.pk.ps.q, this.pk.ps.p) !== 1n) { // (m+1) ^ q mod p
            m = (this.pk.ps.p - m) % this.pk.ps.p; // -(m+1) mod p
        }
        return m; // return (m+1)
    }

    /**
     * TODO check
     * @param m : BigInt
     * @returns BigInt
     */
    extractFromSubgroup(m) {
        let y;
        // if m < q
        if (m < this.pk.ps.q) {
            y = m;
        } else {
            y = (this.pk.ps.p - m) % this.pk.ps.p;
        }
        return y - 1n;
    }

    /**
     *
     * @returns BigInt
     */
    getM() {
        return this.m;
    }

    /**
     *
     * @param r : BigInt
     * @returns {EGCiphertext}
     */
    encrypt(r = null) {
        let m = this.getM();
        if (m === 0n) {
            throw "Can't encrypt 0 with ElGamal"
        }

        if (!r) {
            // random r
            r = randBetween(this.pk.ps.q - 1n);
        }

        // map into subgroup, need to encode the message given that p = 2q+1
        let _m = this.mapIntoSubgroup(m);

        let alpha = modPow(this.pk.ps.g, r, this.pk.ps.p); // alpha = g ^ r mod p
        let beta = (modPow(this.pk.y, r, this.pk.ps.p) * _m) % this.pk.ps.p;

        return new EGCiphertext(alpha, beta, this.pk);
    }


}
