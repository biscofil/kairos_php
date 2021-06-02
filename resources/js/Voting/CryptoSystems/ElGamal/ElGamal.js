//
// inspired by George Danezis, rewritten by Ben Adida.
//

import sha1 from "sha1";
import EGPublicKey from "./EGPublicKey";
import EGCiphertext from "./EGCiphertext";
import EGPlaintext from "./EGPlaintext";

export default class ElGamal {

    /**
     *
     * @return {EGPublicKey}
     */
    static getPublicKeyClass() {
        return EGPublicKey;
    }

    /**
     *
     * @return {EGCiphertext}
     */
    static getCipherTextClass() {
        return EGCiphertext;
    }

    /**
     *
     * @return {EGPlaintext}
     */
    static getPlainTextClass() {
        return EGPlaintext;
    }

    /**
     * a challenge generator based on a list of commitments of
     * proofs of knowledge of plaintext. Just appends A and B with commas.
     * @param commitments
     * @return BigInt
     */
    static disjunctive_challenge_generator(commitments) {
        let strings_to_hash = [];

        // go through all proofs and append the commitments
        commitments.each(function (commitment) {
            strings_to_hash[strings_to_hash.length] = commitment.A.toString();
            strings_to_hash[strings_to_hash.length] = commitment.B.toString();
        });

        // console.log(strings_to_hash);
        // STRINGS = strings_to_hash;
        return new BigInt(sha1(strings_to_hash.join(",")), 16);

    }

    /**
     * a challenge generator for Fiat-Shamir
     * @param commitment
     * @return {*}
     */
    static fiatshamir_challenge_generator(commitment) {
        return this.disjunctive_challenge_generator([commitment]);
    }

    /**
     *
     * @param commitment
     * @returns BigInt
     */
    static fiatshamir_dlog_challenge_generator(commitment) {
        return new BigInt(sha1(commitment.toJSONObject()), 16);
    }

}
