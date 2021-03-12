import {modPow} from "bigint-crypto-utils";

export default class EGPublicKey {

    /**
     * @callback challengeGeneratorCallback
     * @param x : BigInt
     * @return BigInt
     */

    /**
     *
     * @param p : BigInt
     * @param q : BigInt
     * @param g : BigInt
     * @param y : BigInt
     */
    constructor(p, q, g, y) {
        this.p = p;
        this.q = q;
        this.g = g;
        this.y = y;
    }

    /**
     *
     * @return {{p: string, q: string, g: string, y: string}}
     */
    toJSONObject() {
        return {
            g: this.g.toString(),
            p: this.p.toString(),
            q: this.q.toString(),
            y: this.y.toString()
        };
    }

    /**
     *
     * @param proof
     * @param challengeGenerator : ?challengeGeneratorCallback
     * @return {boolean|BigInt}
     */
    verifyKnowledgeOfSecretKey(proof, challengeGenerator) {
        // if challenge_generator is present, we have to check that the challenge was properly generated.
        if (challengeGenerator !== null) {
            if (proof.challenge !== challengeGenerator(proof.commitment)) {
                return false;
            }
        }

        // verify that g^response = s * y^challenge
        return modPow(this.g, proof.response, this.p) === ((modPow(this.y, proof.challenge, this.p) * proof.commitment) % this.p);
    }

    /**
     * check if the decryption factor is correct for this public key, given the proof
     * @param ciphertext : EGCiphertext
     * @param decryptionFactor
     * @param decryptionProof
     * @param challengeGenerator : challengeGeneratorCallback
     * @return {*}
     */
    verifyDecryptionFactor(ciphertext, decryptionFactor, decryptionProof, challengeGenerator) {
        return decryptionProof.verify(
            this.g,
            ciphertext.alpha,
            this.y,
            decryptionFactor,
            this.p,
            this.q,
            challengeGenerator);
    }

    /**
     *
     * @param other : EGPublicKey
     * @return {EGPublicKey}
     */
    multiply(other) {
        // base condition
        if (other === 0 || other === 1) {
            return this;
        }

        // check params
        if (this.p !== other.p) {
            throw "mismatched params";
        }
        if (this.g !== other.g) {
            throw "mismatched params";
        }

        return new EGPublicKey(this.p, this.q, this.g, (this.y * other.y) % this.p);
    }

    /**
     *
     * @param other : EGPublicKey
     * @return {boolean}
     */
    equals(other) {
        return this.p === other.p && this.q === other.q && this.g === other.g && this.y === other.y;
    }

    /**
     *
     * @param d
     * @return {EGPublicKey}
     */
    static fromJSONObject(d) {
        return new EGPublicKey(
            BigInt("0x" + d.p),
            BigInt("0x" + d.q),
            BigInt("0x" + d.g),
            BigInt("0x" + d.y)
        );
    }

}
