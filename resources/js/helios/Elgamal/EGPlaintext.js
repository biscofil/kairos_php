import {modInv, modPow, randBetween} from "bigint-crypto-utils";
import EGCiphertext from "./EGCiphertext";

export default class EGPlaintext {

    /**
     * we need the public key to figure out how to encode m
     * @param m : BigInt
     * @param pk : EGPublicKey
     * @param encode_m : boolean
     */
    constructor(m, pk, encode_m) {
        if (m == null) {
            alert('oy null m');
            return;
        }

        this.pk = pk;

        if (encode_m) {
            // need to encode the message given that p = 2q+1
            let y = m + 1n;
            let test = modPow(y, pk.q, pk.p); // (m+1) ^ q mod p
            if (test === 1n) {
                this.m = y; // m = 1 + m
            } else {
                this.m = modInv(y, pk.p); // m = 1/(m+1) mod p
            }
        } else {
            this.m = m;
        }
    }

    /**
     *
     * @returns BigInt
     */
    getPlaintext() {
        let y;
        // if m < q
        if (this.m < this.pk.q) {
            y = this.m;
        } else {
            y = modInv(this.m, this.pk.p);
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
        if (this.getM() === 0n) {
            throw "Can't encrypt 0 with El Gamal"
        }

        if (!r) {
            r = randBetween(this.pk.q);
        }

        let alpha = modPow(this.pk.g, r, this.pk.p);
        let beta = (modPow(this.pk.y, r, this.pk.p) * this.m) % this.pk.p;

        return new EGCiphertext(alpha, beta, this.pk);
    }

}
