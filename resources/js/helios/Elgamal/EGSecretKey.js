import EGDLogProof from "./EGDLogProof";
import {modPow, randBetween} from "bigint-crypto-utils";
import EGProof from "./EGProof";
import EGPublicKey from "./EGPublicKey";

export default class EGSecretKey {

    /**
     * @callback challengeGeneratorCallback
     * @param x : BigInt
     * @return BigInt
     */

    /**
     *
     * @param x : BigInt
     * @param pk : EGPublicKey
     */
    constructor(x, pk) {
        this.x = x;
        this.pk = pk;
    }

    /**
     *
     * @return {{public_key: {p: string, q: string, g: string, y: string}, x: string}}
     */
    toJSONObject() {
        return {
            public_key: this.pk.toJSONObject(),
            x: this.x.toString()
        };
    }

    /**
     * a decryption factor is *not yet* mod-inverted, because it needs to be part of the proof.
     * @param ciphertext : EGCiphertext
     * @returns {BigInteger|*}
     */
    decryptionFactor(ciphertext) {
        return modPow(ciphertext.alpha, this.x, this.pk.p);
    }

    /**
     *
     * @param ciphertext : EGCiphertext
     * @param decryption_factor : ?BigInt
     * @returns {EGPlaintext}
     */
    decrypt(ciphertext, decryption_factor = null) {
        if (!decryption_factor) {
            decryption_factor = this.decryptionFactor(ciphertext);
        }

        // use the ciphertext's built-in decryption given a list of decryption factors.
        return ciphertext.decrypt([decryption_factor]);
    }

    /**
     *
     * @param ciphertext : EGCiphertext
     * @param challenge_generator : challengeGeneratorCallback
     */
    decryptAndProve(ciphertext, challenge_generator) {
        let dec_factor_and_proof = this.decryptionFactorAndProof(ciphertext, challenge_generator);

        // decrypt, but using the already computed decryption factor
        let plaintext = this.decrypt(ciphertext, dec_factor_and_proof.decryption_factor);

        return {
            'plaintext': plaintext,
            'proof': dec_factor_and_proof.decryption_proof
        };
    }

    /**
     *
     * @param ciphertext : EGCiphertext
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {{decryption_proof: EGProof, decryption_factor: (BigInteger|*)}}
     */
    decryptionFactorAndProof(ciphertext, challenge_generator) {
        let decryption_factor = this.decryptionFactor(ciphertext);

        // the DH tuple we need to prove, given the secret key x, is:
        // g, alpha, y, beta/m
        let proof = EGProof.generate(this.pk.g, ciphertext.alpha, this.x, this.pk.p, this.pk.q, challenge_generator);

        return {
            'decryption_factor': decryption_factor,
            'decryption_proof': proof
        };
    }

    /**
     * generate a proof of knowledge of the secret exponent x
     * @param challengeGenerator : challengeGeneratorCallback
     * @returns {EGDLogProof}
     */
    proveKnowledge(challengeGenerator) {
        // generate random w
        let w = randBetween(this.pk.q);

        // compute s = g^w for random w.
        let s = modPow(this.pk.g, w, this.pk.p);

        // get challenge
        let challenge = challengeGenerator(s);

        // compute response
        let response = (w + (this.x * challenge)) % this.pk.q;

        return new EGDLogProof(s, challenge, response);
    }

    /**
     *
     * @param d : Object
     * @return {EGSecretKey}
     */
    static fromJSONObject(d) {
        return new EGSecretKey(
            BigInt("0x" + d.x),
            EGPublicKey.fromJSONObject(d.pk)
        );
    }
}
