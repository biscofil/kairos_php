<template>
    <div>
        <div id="wrapper">
            <div id="banner">
                Helios Election Verifier
            </div>
            <div id="content">

                <div id="verifier_loading">
                    loading verifier ...
                </div>

                <div id="verifier" align="center" style="display:none;">
                    Enter the Election URL:
                    <form onsubmit="try{load_election_and_ballots(election_url.value);} catch (e) {} return false;">
                        <input type="text" size="50" name="election_url" id="election_url"/><br/>
                        <input type="submit" value="start verification"/>
                    </form>
                </div>

                <br/><br/>
                <div id="results">
                </div>
            </div>
        </div>
        <div id="applet_div">
        </div>
    </div>
</template>

<script>

import "./css/booth.css";
import "./css/forms.css";

export default {
    name: "Verifier",

    mounted() {
        /* var election_url = $.query.get('election_url');
         $('#election_url').val(election_url);*/
    },

    methods: {
        result_append(str) {
            $('#results').append(str).append("<br />");
        },

        pretty_result(result) {
            return result ? "VERIFIED" : "FAIL";
        },

        load_ballots(election_url, ballot_list, ballots, final_callback) {
            // the ballots array is the place where we build up the list of ballots

            // end of the iteration?
            if (ballot_list.length == ballots.length) {
                final_callback(ballots);
                return;
            }

            result_append("loading ballot for voter #" + (ballots.length + 1));

            // get the next ballot
            this.$http.get(election_url + "/ballots/" + ballot_list[ballots.length].slug + '/last')
                .then(function (response) {
                    var new_ballot = JSON.parse(response.data);
                    ballots.push(new_ballot);
                    if (new_ballot.vote == null) {
                        result_append("no ballot for this voter #" + ballots.length);
                    } else {
                        result_append("FOUND a ballot for voter #" + ballots.length);
                    }
                    load_ballots(election_url, ballot_list, ballots, final_callback);
                });
        },

        // load the ballot list in increments of 50, for long ballots
        load_ballot_list(election_url, ballot_list, after, final_callback) {
            var url = election_url + "/voters/?limit=50";
            if (after)
                url += "&after=" + after;

            this.$http.get(url)
                .then(function (response) {
                    var new_ballot_list = response.data;

                    // done, no more ballots?
                    if (new_ballot_list.length === 0) {
                        return final_callback(ballot_list);
                    }

                    // not done, add to the list
                    ballot_list = ballot_list.concat(new_ballot_list);
                    after = ballot_list[ballot_list.length - 1].slug;

                    // and iterate
                    load_ballot_list(election_url, ballot_list, after, final_callback);
                });
        },

        load_election_and_ballots(election_url) {

            result_append("<h3>Election</h3>");
            result_append("loading election...");

            var overall_result = true;

            // the hash will be computed within the setup function call now
            this.$http.get(election_url)
                .then(function (raw_json) {
                    try {
                        let election = Election.fromJSONString(raw_json);
                        result_append("loaded election: " + election.name);
                        result_append("election fingerprint: " + election.get_hash());

                        var tally = [];

                        election.questions.forEach(function (qnum, q) {
                            if (q.tally_type != "homomorphic") {
                                result_append("PROBLEM: this election is not a straight-forward homomorphic-tally election. As a result, Helios cannot currently verify it.");
                                return;
                            }

                            tally[qnum] = $(q.answers).map(function (anum, a) {
                                return 1;
                            });
                        });

                        result_append("loading list of voters...");

                        // load voter list
                        load_ballot_list(election_url, [], null, function (ballot_list) {
                            result_append("loaded voter list, now loading ballots for each..");

                            // load all ballots
                            load_ballots(election_url, ballot_list, [], function (ballots) {
                                result_append("");
                                result_append("<h3>Ballots</h3>");
                                // now load each ballot
                                $(ballots).each(function (i, cast_vote) {

                                    if (cast_vote.vote == null) {
                                        return;
                                    }

                                    var vote = HELIOS.EncryptedVote.fromJSONObject(cast_vote.vote, election);
                                    result_append("Voter #" + (i + 1));
                                    result_append("-- slug: " + cast_vote.voter_slug);
                                    result_append("-- Ballot Tracking Number: " + vote.get_hash());

                                    vote.verifyProofs(election.public_key, function (answer_num, choice_num, result, choice) {
                                        overall_result = overall_result && result;
                                        if (choice_num != null) {
                                            // keep track of tally
                                            tally[answer_num][choice_num] = choice.multiply(tally[answer_num][choice_num]);

                                            result_append("Question #" + (answer_num + 1) + ", Option #" + (choice_num + 1) + " -- " + pretty_result(result));
                                        } else {
                                            result_append("Question #" + (answer_num + 1) + " OVERALL -- " + pretty_result(result));
                                        }
                                    });

                                    result_append("");
                                });

                                // get the election result
                                this.$http.get(election_url + "/result")
                                    .then(function (response) {
                                        var results = response.data;

                                        // get the trustees and proofs
                                        this.$http.get(election_url + "/trustees/")
                                            .then(function (response) {
                                                let trustees = response.data;

                                                // create the Helios objects
                                                trustees = trustees.map(function (i, trustee) {
                                                    return HELIOS.Trustee.fromJSONObject(trustee)
                                                });

                                                // the public key that we'll check
                                                var combined_key = 1;

                                                result_append("<h3>Trustees</h3>");
                                                // verify the keys
                                                trustees.forEach(function (i, trustee) {
                                                    result_append("Trustee #" + (i + 1) + ": " + trustee.email);
                                                    if (trustee.public_key.verifyKnowledgeOfSecretKey(trustee.pok, ElGamal.fiatshamir_dlog_challenge_generator)) {
                                                        result_append("-- PK " + trustee.public_key_hash + " -- VERIFIED.");

                                                        // FIXME check the public key hash
                                                    } else {
                                                        result_append("==== ERROR for PK of trustee " + trustee.email);
                                                        overall_result = false;
                                                    }

                                                    combined_key = trustee.public_key.multiply(combined_key);

                                                    result_append("");
                                                });

                                                // verify the combination of the keys into the final public key
                                                if (combined_key.equals(election.public_key)) {
                                                    result_append("election public key CORRECTLY FORMED");
                                                } else {
                                                    result_append("==== ERROR, election public key doesn't match");
                                                    overall_result = false;
                                                }

                                                result_append("<h3>Tally</h3>");

                                                tally.forEach(function (q_num, q) {
                                                    result_append("Question #" + (q_num + 1) + ": " + election.questions[q_num].slug);
                                                    q.forEach(function (a_num, a) {
                                                        var plaintext = new ElGamal.Plaintext(modPow(election.public_key.g, BigInt.fromInt(results[q_num][a_num]), election.public_key.p), election.public_key);

                                                        var check = true;
                                                        result_append("Answer #" + (a_num + 1) + ": " + election.questions[q_num].answers[a_num] + " - COUNT = " + results[q_num][a_num]);

                                                        var decryption_factors = [];

                                                        // go through the trustees' decryption factors and verify each one
                                                        trustees.forEach(function (t_num, trustee) {
                                                            if (trustee.public_key.verifyDecryptionFactor(a, trustee.decryption_factors[q_num][a_num],
                                                                trustee.decryption_proofs[q_num][a_num], ElGamal.fiatshamir_challenge_generator)) {
                                                                result_append("-- Trustee " + trustee.email + ": decryption factor verifies");
                                                            } else {
                                                                result_append("==== ERROR with Trustee " + trustee.email + ": decryption factor does not verify");
                                                                check = false;
                                                                overall_result = false;
                                                            }

                                                            decryption_factors.push(trustee.decryption_factors[q_num][a_num]);
                                                        });

                                                        // recheck decryption factors
                                                        var expected_value = modPow(election.public_key.g, BigInt.fromInt(results[q_num][a_num]), election.public_key.p);
                                                        var recomputed_value = a.decrypt(decryption_factors).getM();
                                                        if (expected_value.equals(recomputed_value)) {
                                                        } else {
                                                            check = false;
                                                            overall_result = false;
                                                        }

                                                        result_append("-" + pretty_result(check));
                                                    });

                                                });

                                                result_append("<h3>FINAL RESULT</h3>");

                                                if (overall_result) {
                                                    result_append("ELECTION FULLY VERIFIED -- SUCCESS!");
                                                } else {
                                                    result_append("VERIFICATION FAILED");
                                                }
                                            });
                                    });
                            });

                        });
                    } catch (error) {
                        result_append("<p>It appears that you are trying to verify a private election.</p>");
                        result_append('<p>You can log in as a valid voter or log in as the election admin.</p>');
                        result_append('<a class="btn" href="' + election_url + '">Log in as a valid voter </a>');
                        result_append('<a class="btn" href="/auth/?return_url=/verifier/verify.html?election_url=' + election_url + '">Log in as the election admin</a>');
                    }
                });

        },


        verify_ballot(election_raw_json, encrypted_vote_json, status_cb) {
            var overall_result = true;
            try {
                let election = Election.fromJSONString(election_raw_json);
                var election_hash = election.get_hash();
                status_cb("election fingerprint is " + election_hash);

                // display ballot fingerprint
                let encrypted_vote = EncryptedVote.fromJSONObject(encrypted_vote_json, election);
                status_cb("smart ballot tracker is " + encrypted_vote.get_hash());

                // check the hash
                if (election_hash === encrypted_vote.election_hash) {
                    status_cb("election fingerprint matches ballot");
                } else {
                    overall_result = false;
                    status_cb("PROBLEM = election fingerprint does not match");
                }

                // display the ballot as it is claimed to be
                status_cb("Ballot Contents:");
                election.questions.forEach(function (q, qnum) {
                    if (q.tally_type !== "homomorphic") {
                        status_cb("WARNING: the tally type for this question is not homomorphic. Verification may fail because this verifier is only set up to handle homomorphic ballots.");
                    }

                    var answer_pretty_list = _(encrypted_vote.encrypted_answers[qnum].answer).map(function (aindex, anum) {
                        return q.answers[aindex];
                    });
                    status_cb("Question #" + (qnum + 1) + " - " + q.slug + " : " + answer_pretty_list.join(", "));
                });

                // verify the encryption
                if (encrypted_vote.verifyEncryption(election.questions, election.public_key)) {
                    status_cb("Encryption Verified");
                } else {
                    overall_result = false;
                    status_cb("PROBLEM = Encryption doesn't match.");
                }

                // verify the proofs
                if (encrypted_vote.verifyProofs(election.public_key, function (ea_num, choice_num, result) {
                })) {
                    status_cb("Proofs ok.");
                } else {
                    overall_result = false;
                    status_cb("PROBLEM = Proofs don't work.");
                }
            } catch (e) {
                status_cb('problem parsing election or ballot data structures, malformed inputs: ' + e.toString());
                overall_result = false;
            }

            return overall_result;
        }

    }

}

</script>

<style scoped>

</style>
