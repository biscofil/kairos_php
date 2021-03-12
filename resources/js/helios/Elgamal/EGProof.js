import {modInv, modPow, randBetween} from "bigint-crypto-utils";

export default class EGProof {

    /**
     * @callback challengeGeneratorCallback
     * @param x : BigInt
     * @return BigInt
     */

    /**
     *
     * @param A : BigInt
     * @param B : BigInt
     * @param challenge : BigInt
     * @param response : BigInt
     */
    constructor(A, B, challenge, response) {
        this.commitment = {};
        this.commitment.A = A;
        this.commitment.B = B;
        this.challenge = challenge;
        this.response = response;
    }

    /**
     *
     * @returns {{response: BigInt, challenge: BigInt, commitment: {A, B}}}
     */
    toJSONObject() {
        return {
            challenge: this.challenge,
            commitment: {
                A: this.commitment.A,
                B: this.commitment.B
            },
            response: this.response
        }
    }

    /**
     * verify a DH tuple proof
     * @param little_g : BigInt
     * @param little_h : BigInt
     * @param big_g : BigInt
     * @param big_h : BigInt
     * @param p : BigInt
     * @param q : BigInt
     * @param challenge_generator : challengeGeneratorCallback
     * @returns {boolean}
     */
    verify(little_g, little_h, big_g, big_h, p, q, challenge_generator = null) {

        // check that little_g^response = A * big_g^challenge
        let first_check = modPow(little_g, this.response, p) === (modPow(big_g, this.challenge, p) * this.commitment.A) % p;

        // check that little_h^response = B * big_h^challenge
        let second_check = modPow(little_h, this.response, p) === (modPow(big_h, this.challenge, p) * this.commitment.B) % p;

        let third_check = true;

        if (challenge_generator) {
            third_check = this.challenge === challenge_generator(this.commitment);
        }

        return first_check && second_check && third_check;
    }

    /**
     *
     * @param d
     * @returns {EGProof}
     */
    static fromJSONObject(d) {
        return new EGProof(
            BigInt(d.commitment.A),
            BigInt(d.commitment.B),
            BigInt(d.challenge),
            BigInt(d.response)
        );
    }

    /**
     * a generic way to prove that four values are a DH tuple.
     * a DH tuple is g,h,G,H where G = g^x and H=h^x
     * challenge generator takes a commitment, whose subvalues are A and B
     * all modulo p, with group order q, which we provide just in case.
     * as it turns out, G and H are not necessary to generate this proof, given that they're implied by x.
     * @param little_g : BigInt
     * @param little_h : BigInt
     * @param x : BigInt
     * @param p : BigInt
     * @param q : BigInt
     * @param challenge_generator : challengeGeneratorCallback
     */
    generate(little_g, little_h, x, p, q, challenge_generator) {
        // generate random w
        let w = randBetween(q);

        // compute A=little_g^w, B=little_h^w
        let commitment = {};
        commitment.A = modPow(little_g, w, p);
        commitment.B = modPow(little_h, w, p);

        // Get the challenge from the callback that generates it
        let challenge = challenge_generator(commitment);

        // Compute response = w + x * challenge
        let response = (w + (x * challenge)) % q;

        // create a proof instance
        return new EGProof(
            commitment.A,
            commitment.B,
            challenge,
            response
        );
    }

    /**
     * simulate a a DH-tuple proof, with a potentially assigned challenge (but can be null)
     * @param little_g : BigInt
     * @param little_h : BigInt
     * @param big_g : BigInt
     * @param big_h : BigInt
     * @param p : BigInt
     * @param q : BigInt
     * @param challenge : ?BigInt
     * @returns {EGProof}
     */
    simulate(little_g, little_h, big_g, big_h, p, q, challenge = null) {

        // generate a random challenge if not provided
        if (challenge === null) {
            challenge = randBetween(q);
        }

        // random response, does not even need to depend on the challenge
        let response = randBetween(q);

        // now we compute A and B
        // A = little_g ^ w, and at verification time, g^response = G^challenge * A, so A = (G^challenge)^-1 * g^response
        let A = (modInv(modPow(big_g, challenge, p), p) * modPow(little_g, response, p)) % p;

        // B = little_h ^ w, and at verification time, h^response = H^challenge * B, so B = (H^challenge)^-1 * h^response
        let B = (modInv(modPow(big_h, challenge, p), p) * modPow(little_h, response, p)) % p;

        return new EGProof(A, B, challenge, response);
    }
}
