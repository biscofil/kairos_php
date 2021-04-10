import EGPlaintext from "./EGPlaintext";
import EGProof from "./EGProof";
import {modInv} from "bigint-crypto-utils";
import EGDisjunctiveProof from "./EGDisjunctiveProof";

export default class EGCiphertext {

    /**
     * @callback challengeGeneratorCallback
     * @param x : BigInt
     * @return BigInt
     */

    /**
     * @param alpha : BigInt
     * @param beta : BigInt
     * @param pk : EGPublicKey
     */
    constructor(alpha, beta, pk) {
        this.alpha = alpha;
        this.beta = beta;
        this.pk = pk;
    }

    /**
     *
     * @returns {string}
     */
    toString() {
        return this.alpha.toString(16) + ',' + this.beta.toString(16);
    }

    /**
     * Returns the dict of hex
     * @returns {{alpha: string, beta: string}}
     */
    toJSONObject() {
        return {
            alpha: this.alpha.toString(16),
            beta: this.beta.toString(16)
        }
    }

    /**
     *
     * @param other : EGCiphertext
     * @returns {EGCiphertext}
     */
    multiply(other) {
        // special case if other is 1 to enable easy aggregate ops
        if (other === 1) { // TODO
            return this;
        }

        // homomorphic multiply
        return new EGCiphertext(
            (this.alpha * other.alpha) % this.pk.p,
            (this.beta * other.beta) % this.pk.p,
            this.pk
        );
    }

    /**
     * a decryption method by decryption factors
     * @param list_of_dec_factors : BigInt[]
     * @returns {EGPlaintext}
     */
    decrypt(list_of_dec_factors) {
        let running_decryption = this.beta;
        let self = this;
        list_of_dec_factors.forEach(function (dec_factor) {
            running_decryption = (modInv(dec_factor, self.pk.p) * running_decryption) % self.pk.p;
        });
        return new EGPlaintext(running_decryption, this.pk, false);
    }

    /**
     *
     * @param plaintext : EGPlaintext
     * @param randomness : BigInt
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {EGProof}
     */
    generateProof(plaintext, randomness, challenge_generator) {
        // DH tuple to prove is
        // g, y, alpha, beta/m
        // with dlog randomness
        return EGProof.generate(
            this.pk.g,
            this.pk.y,
            randomness,
            this.pk.p,
            this.pk.q,
            challenge_generator);
    }

    /**
     *
     * @param plaintext : EGPlaintext
     * @param challenge : challengeGeneratorCallback // TODO check
     * @returns {EGProof}
     */
    simulateProof(plaintext, challenge = null) {

        // compute beta/plaintext, the completion of the DH tuple
        let beta_over_plaintext = (this.beta * modInv(plaintext.m, this.pk.p)) % this.pk.p;

        // the DH tuple we are simulating here is
        // g, y, alpha, beta/m
        return EGProof.simulate(
            this.pk.g,
            this.pk.y,
            this.alpha,
            beta_over_plaintext,
            this.pk.p,
            this.pk.q,
            challenge); // TODO expects ?bigint, not ?challengeGeneratorCallback
    }

    /**
     *
     * @param plaintext : EGPlaintext
     * @param proof : EGProof
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {boolean}
     */
    verifyProof(plaintext, proof, challenge_generator = null) {
        // DH tuple to verify is
        // g, y, alpha, beta/m
        let beta_over_m = (this.beta * modInv(plaintext.m, this.pk.p)) % this.pk.p;
        return proof.verify(this.pk.g, this.pk.y, this.alpha, beta_over_m, this.pk.p, this.pk.q, challenge_generator);
    }

    /**
     * @param plaintext : EGPlaintext
     * @param proof : EGProof
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {boolean}
     */
    verifyDecryptionProof(plaintext, proof, challenge_generator = null) {
        // DH tuple to verify is
        // g, alpha, y, beta/m
        // since the proven dlog is the secret key x, y=g^x.
        let beta_over_m = (this.beta * modInv(plaintext.m, this.pk.p)) % this.pk.p;
        return proof.verify(this.pk.g, this.alpha, this.pk.y, beta_over_m, this.pk.p, this.pk.q, challenge_generator);
    }

    /**
     *
     * @param list_of_plaintexts
     * @param real_index : Number
     * @param randomness
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {EGDisjunctiveProof}
     */
    generateDisjunctiveProof(list_of_plaintexts, real_index, randomness, challenge_generator) {
        // go through all plaintexts and simulate the ones that must be simulated.
        // note how the interface is as such so that the result does not reveal which is the real proof.
        let self = this;
        let proofs = list_of_plaintexts.map(function (plaintext, p_num) {
            if (p_num === real_index) {
                // no real proof yet
                return {};
            } else {
                // simulate!
                return self.simulateProof(plaintext);
            }
        });

        // do the real proof
        let real_proof = this.generateProof(list_of_plaintexts[real_index], randomness, function (commitment) {
            // now we generate the challenge for the real proof by first determining
            // the challenge for the whole disjunctive proof.

            // set up the partial real proof so we're ready to get the hash;
            proofs[real_index] = {'commitment': commitment};

            // get the commitments in a list and generate the whole disjunctive challenge
            let commitments = proofs.map(function (proof) {
                return proof.commitment;
            });

            let disjunctive_challenge = challenge_generator(commitments);

            // now we must subtract all of the other challenges from this challenge.

            /** @type BigInt **/
            let real_challenge = disjunctive_challenge;
            proofs.each(function (proof, proof_num) {
                if (proof_num !== real_index) {
                    real_challenge = real_challenge + modInv(proof.challenge, self.pk.q);
                }
            });

            // make sure we mod q, the exponent modulus
            return real_challenge % self.pk.q;
        });

        // set the real proof
        proofs[real_index] = real_proof;
        return new EGDisjunctiveProof(proofs);
    }

    /**
     *
     * @param list_of_plaintexts : EGPlaintext[]
     * @param disj_proof : EGDisjunctiveProof
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {boolean|*}
     */
    verifyDisjunctiveProof(list_of_plaintexts, disj_proof, challenge_generator) {
        let proofs = disj_proof.proofs;

        // for loop because we want to bail out of the inner loop
        // if we fail one of the verifications.
        for (let i = 0; i < list_of_plaintexts.length; i++) {
            if (!this.verifyProof(list_of_plaintexts[i], proofs[i])) {
                return false;
            }
        }

        // check the overall challenge

        // first the one expected from the proofs
        let commitments = proofs.map(function (proof) {
            return proof.commitment;
        });
        let expected_challenge = challenge_generator(commitments);

        // then the one that is the sum of the previous one.
        let sum = 0n;
        let self = this;
        proofs.forEach(function (proof) {
            sum = (sum + proof.challenge) % self.pk.q;
        });

        return expected_challenge.equals(sum);
    }

    /**
     * @param other : EGCiphertext
     * @return {boolean}
     */
    equals(other) {
        return this.alpha === other.alpha && this.beta === other.beta;
    }

    /**
     *
     * @param d : Object
     * @param pk : EGPublicKey
     * @returns {EGCiphertext}
     */
    static fromJSONObject(d, pk) {
        return new EGCiphertext(
            BigInt(d.alpha),
            BigInt(d.beta),
            pk
        );
    }
}



