import EncryptedAnswer from "./EncryptedAnswer";
import ElGamal from "../Voting/CryptoSystems/Elgamal/ElGamal";
import Utils from "./Utils/Utils";

const {SHA256} = require("sha2");

export default class EncryptedVote {

    /**
     *
     * @param election : Election
     * @param answers : Answer[]
     * @param progress : Progress
     */
    constructor(election, answers = [], progress = null) {
        // empty constructor
        if (election == null) {
            return;
        }

        // keep information about the election around
        this.election_slug = election.slug;
        this.election_hash = election.get_hash();
        this.election = election;

        if (answers == null) {
            return;
        }

        let n_questions = election.questions.length;

        if (progress) {
            // set up the number of ticks
            election.questions.forEach(function (q, q_num) {
                // + 1 for the overall proof
                progress.addTicks(q.answers.length);
                if (q.max != null) {
                    progress.addTicks(q.max);
                }
            });

            progress.addTicks(0, n_questions);
        }

        // loop through questions
        this.encrypted_answers = [];
        for (let i = 0; i < n_questions; i++) {
            this.encrypted_answers[i] = new EncryptedAnswer(election.questions[i], answers[i], election.public_key, progress);
        }
    }

    /**
     *
     * @return {string}
     */
    toString() {
        // for each question, get the encrypted answer as a string
        let answer_strings = this.encrypted_answers.map(function (a) {
            return a.toString();
        });
        return answer_strings.join("//");
    }

    /**
     *
     */
    clearPlaintexts() {
        this.encrypted_answers.forEach(function (ea) {
            ea.clearPlaintexts();
        });
    }

    /**
     *
     * @param questions : Question[]
     * @param pk : EGPublicKey
     * @return {boolean}
     */
    verifyEncryption(questions, pk) {
        let overallResult = true;
        this.encrypted_answers.forEach(function (ea, i) {
            overallResult = overallResult && ea.verifyEncryption(questions[i], pk);
        });
        return overallResult;
    }

    /**
     *
     * @param includePlaintext : boolean
     * @return {{election_hash: *, answers: *[], election_slug: (Election.slug|String)}}
     */
    toJSONObject(includePlaintext) {
        let answers = this.encrypted_answers.map(function (ea, i) {
            return ea.toJSONObject(includePlaintext);
        });

        return {
            answers: answers,
            election_hash: this.election_hash,
            election_slug: this.election_slug
        };
    }

    /**
     *
     * @return {string}
     */
    get_hash() {
        return SHA256(JSON.stringify(this.toJSONObject())).toString("base64"); // TODO
    }

    /**
     *
     * @return {{election_hash: (string|*), answers: *[], election_slug: null}}
     */
    get_audit_trail() {
        return this.toJSONObject(true);
    }

    /**
     *
     * @param pk : EGPublicKey
     * @param outcome_callback
     * @return {boolean}
     */
    verifyProofs(pk, outcome_callback) {
        let zero_or_one = Utils.generatePlaintexts(pk, 0, 1); // TODO

        let VALID_P = true;

        let self = this;

        // for each question and associate encrypted answer
        this.encrypted_answers.forEach(function (enc_answer, ea_num) {
            let overall_result = 1;

            // the max number of answers (decides whether this is approval or not and requires an overall proof)
            let max = self.election.questions[ea_num].max;

            // go through each individual proof
            enc_answer.choices.forEach(function (choice, choice_num) {
                let result = choice.verifyDisjunctiveProof(
                    zero_or_one,
                    enc_answer.individual_proofs[choice_num],
                    ElGamal.disjunctive_challenge_generator);
                outcome_callback(ea_num, choice_num, result, choice);

                VALID_P = VALID_P && result;

                // keep track of homomorphic product, if needed
                if (max != null) {
                    overall_result = choice.multiply(overall_result);
                }
            });

            if (max != null) {
                // possible plaintexts [0, 1, .. , question.max]
                let plaintexts = Utils.generatePlaintexts(
                    pk,
                    self.election.questions[ea_num].min,
                    self.election.questions[ea_num].max); // TODO

                // check the proof on the overall product
                let overall_check = overall_result.verifyDisjunctiveProof(
                    plaintexts,
                    enc_answer.overall_proof,
                    ElGamal.disjunctive_challenge_generator);

                outcome_callback(ea_num, null, overall_check, null);
                VALID_P = VALID_P && overall_check;
            } else {
                // check to make sure the overall_proof is null, since it's approval voting
                VALID_P = VALID_P && (enc_answer.overall_proof == null)
            }
        });

        return VALID_P;
    }

    /**
     *
     * @param d : Object
     * @param election : Election
     * @return {EncryptedVote|null}
     */
    static fromJSONObject(d, election) {
        if (d == null) {
            return null;
        }
        let ev = new EncryptedVote(election);

        ev.encrypted_answers = d.answers.map(function (ea) {
            return EncryptedAnswer.fromJSONObject(ea, election);
        });

        ev.election_hash = d.election_hash;
        ev.election_slug = d.election_slug;

        return ev;
    };

    /**
     * create an encrypted vote from a set of answers
     * @param election : Election
     * @param enc_answers : EncryptedAnswer[]
     * @return {EncryptedVote}
     */
    fromEncryptedAnswers(election, enc_answers) {
        let enc_vote = new EncryptedVote(election, null);
        enc_vote.encrypted_answers = [];
        enc_answers.forEach(function (enc_answer, answer_num) {
            enc_vote.encrypted_answers[answer_num] = enc_answer;
        });
        return enc_vote;
    };
}
