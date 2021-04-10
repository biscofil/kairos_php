import EGCiphertext from "../Voting/CryptoSystems/Elgamal/EGCiphertext";
import EGDisjunctiveProof from "../Voting/CryptoSystems/Elgamal/EGDisjunctiveProof";
import {randBetween} from "bigint-crypto-utils";
import {generatePlaintexts} from "./Utils/Utils";
import ElGamal from "../Voting/CryptoSystems/Elgamal/ElGamal";

export default class EncryptedAnswer {

    /**
     *
     * @param question : Question
     * @param answer : Answer
     * @param pk : EGPublicKey
     * @param progress : Progress
     */
    constructor(question, answer, pk, progress) {

        // if nothing in the constructor
        if (question == null) {
            return;
        }

        // store answer
        // CHANGE 2008-08-06: answer is now an *array* of answers, not just a single integer
        this.answer = answer;

        // do the encryption
        let enc_result = this.doEncryption(question, answer, pk, null, progress);

        this.choices = enc_result.choices;
        this.randomness = enc_result.randomness;
        this.individual_proofs = enc_result.individual_proofs;
        this.overall_proof = enc_result.overall_proof;
    }

    /**
     *
     * @param question : Question
     * @param answer : Answer
     * @param pk : EGPublicKey
     * @param randomness : BigInt[]
     * @param progress : Progress
     * @return {{individual_proofs: [], overall_proof: null, randomness: *[], choices: []}}
     */
    doEncryption(question, answer, pk, randomness = null, progress = null) {
        let i;
        let choices = [];
        let individual_proofs = [];
        let overall_proof = null;

        // possible plaintexts [question.min .. , question.max]
        let plaintexts = null;
        if (question.max != null) {
            plaintexts = generatePlaintexts(pk, question.min, question.max);
        }

        let zero_one_plaintexts = generatePlaintexts(pk, 0, 1);

        // keep track of whether we need to generate new randomness
        let generate_new_randomness = false;
        if (!randomness) {
            randomness = [];
            generate_new_randomness = true;
        }

        // keep track of number of options selected.
        let num_selected_answers = 0;

        // go through each possible answer and encrypt either a g^0 or a g^1.
        for (i = 0; i < question.answers.length; i++) {
            let index, plaintext_index;
            // if this is the answer, swap them so m is encryption 1 (g)
            if (answer.indexOf(i) !== -1) {
                plaintext_index = 1;
                num_selected_answers += 1;
            } else {
                plaintext_index = 0;
            }

            // generate randomness?
            if (generate_new_randomness) {
                randomness[i] = randBetween(pk.q);
            }

            choices[i] = ElGamal.encrypt(pk, zero_one_plaintexts[plaintext_index], randomness[i]);

            // generate proof
            if (generate_new_randomness) {
                // generate proof that this ciphertext is a 0 or a 1
                individual_proofs[i] = choices[i].generateDisjunctiveProof(
                    zero_one_plaintexts,
                    plaintext_index,
                    randomness[i],
                    ElGamal.disjunctive_challenge_generator);
            }

            if (progress) {
                progress.tick();
            }
        }

        if (generate_new_randomness && question.max != null) {
            // we also need proof that the whole thing sums up to the right number
            // only if max is non-null, otherwise it's full approval voting

            // compute the homomorphic sum of all the options
            let hom_sum = choices[0];
            let rand_sum = randomness[0];
            for (i = 1; i < question.answers.length; i++) {
                hom_sum = hom_sum.multiply(choices[i]);
                rand_sum = (rand_sum + randomness[i]) % pk.q;
            }

            // prove that the sum is 0 or 1 (can be "blank vote" for this answer)
            // num_selected_answers is 0 or 1, which is the index into the plaintext that is actually encoded
            //
            // now that "plaintexts" only contains the array of plaintexts that are possible starting with min
            // and going to max, the num_selected_answers needs to be reduced by min to be the proper index
            let overall_plaintext_index = num_selected_answers;
            if (question.min) {
                overall_plaintext_index -= question.min;
            }

            overall_proof = hom_sum.generateDisjunctiveProof(
                plaintexts,
                overall_plaintext_index,
                rand_sum,
                ElGamal.disjunctive_challenge_generator);

            if (progress) {
                for (i = 0; i < question.max; i++) {
                    progress.tick();
                }
            }
        }

        return {
            'choices': choices,
            'randomness': randomness,
            'individual_proofs': individual_proofs,
            'overall_proof': overall_proof
        };
    }

    /**
     *
     */
    clearPlaintexts() {
        this.answer = null;
        this.randomness = null;
    }

    /**
     * FIXME: should verifyEncryption really generate proofs? Overkill.
     * @param question : Question
     * @param pk : EGPublicKey
     * @return {boolean}
     */
    verifyEncryption(question, pk) {
        let result = this.doEncryption(question, this.answer, pk, this.randomness);

        // check that we have the same number of ciphertexts
        if (result.choices.length !== this.choices.length) {
            return false;
        }

        // check the ciphertexts
        for (let i = 0; i < result.choices.length; i++) {
            if (!result.choices[i].equals(this.choices[i])) {
                // alert ("oy: " + result.choices[i] + "/" + this.choices[i]);
                return false;
            }
        }

        // we made it, we're good
        return true;
    }

    /**
     *
     * @return {string}
     */
    toString() {
        // get each ciphertext as a JSON string
        let choices_strings = this.choices.map(function (c) {
            return c.toString();
        });
        return choices_strings.join("|");
    }

    /**
     *
     * @param include_plaintext : boolean
     * @return {{individual_proofs: *[], choices: *[]}}
     */
    toJSONObject(include_plaintext) {
        let return_obj = {
            'choices': this.choices.map(function (choice) {
                return choice.toJSONObject();
            }),
            'individual_proofs': this.individual_proofs.map(function (disj_proof) {
                return disj_proof.toJSONObject();
            })
        };

        if (this.overall_proof != null) {
            return_obj.overall_proof = this.overall_proof.toJSONObject();
        } else {
            return_obj.overall_proof = null;
        }

        if (include_plaintext) {
            return_obj.answer = this.answer;
            return_obj.randomness = this.randomness.map(function (r) {
                return r.toJSONObject();
            });
        }

        return return_obj;
    }

    /**
     *
     * @param d
     * @param election : Election
     * @return {EncryptedAnswer}
     */
    static fromJSONObject(d, election) {
        let ea = new EncryptedAnswer(null, null, null, null); // TODO

        ea.choices = d.choices.map(function (choice) {
            return EGCiphertext.fromJSONObject(choice, election.public_key);
        });

        ea.individual_proofs = d.individual_proofs.map(function (p) {
            return EGDisjunctiveProof.fromJSONObject(p);
        });

        ea.overall_proof = EGDisjunctiveProof.fromJSONObject(d.overall_proof);

        // possibly load randomness and plaintext
        if (d.randomness) {
            ea.randomness = d.randomness.map(function (r) {
                return BigInt(r);
            });
            ea.answer = d.answer;
        }

        return ea;
    };
}
