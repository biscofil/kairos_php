<template>
    <div>

        <div id="wrapper">
            <div id="banner">
                Helios Single-Ballot Verifier
            </div>
            <div id="content">

                <div id="verifier_loading">
                    Loading verifier...
                </div>

                <div id="dummy_bigint" style="display:none;">
                    Your browser does not have the Java plugin installed.<br/><br/>
                    At this time, the Java plugin is required for browser-based ballot auditing, although it is not
                    required for
                    ballot preparation.
                </div>

                <div id="verifier" style="display:none;">
                    <p style="font-size: 16pt;">
                        This single-ballot verifier lets you enter an audited ballot<br/>and verify that it was prepared
                        correctly.
                    </p>

                    <form
                        onsubmit="try {verify_single_ballot(this.election_url.value, this.audit_trail.value);} catch (e) {E = e;} return false;">
                        Enter the Election URL:
                        <input type="text" size="50" name="election_url" id="election_url"/><br/>

                        <p>
                            Your Ballot:
                        </p>

                        <textarea name="audit_trail" cols="80" rows="7"></textarea>
                        <br/><br/>
                        <input type="submit" class="pretty" value="Verify"/>
                    </form>

                    <br/><br/>
                    <div id="results">
                    </div>
                    <img id="loading" src="/assets/img/loading.gif" style="display:none;"/>
                </div>
            </div>

        </div>

        <div id="applet_div">
        </div>
    </div>
</template>

<script>
export default {
    name: "SingleBallotVerify",

    mounted() {
        BigInt.setup(function () {
            $('#verifier_loading').hide();

            if (BigInt.is_dummy) {
                $('#dummy_bigint').show();
                return;
            }

            $('#verifier').show();
            var election_url = $.query.get('election_url');
            $('#election_url').val(election_url);
        }, function () {
            $('#dummy_bigint').show();
        });
    },

    methods: {

        result_append(str) {
            $('#results').append(str).append("<br />");
        },

        verify_single_ballot(election_url, audit_trail) {
            var encrypted_vote_json = JSON.parse(audit_trail);

            result_append("loading election...");

            // quick and dirty detection of cast ballot
            if (encrypted_vote_json['cast_at']) {
                result_append("\n\nIt looks like you are trying to verify a cast ballot. That can't be done, only audited ballots can be verified.");
                return;
            }

            $('#loading').show();

            var after_computation = function (overall_result) {
                result_append("<br />");

                $('#loading').hide();

                if (overall_result) {
                    result_append('SUCCESSFUL VERIFICATION, DONE!');
                } else {
                    result_append('PROBLEM - THIS BALLOT DOES NOT VERIFY.');
                }
            };

            // the hash will be computed within the setup function call now
            $.ajax({
                url: election_url, success: function (raw_json) {
                    if (window.Worker) {
                        var verifier_worker = new window.Worker("verifierworker.js");
                        verifier_worker.onmessage = function (event) {
                            if (event.data.type == 'log')
                                return console.log(event.data.msg);

                            if (event.data.type == 'status')
                                return result_append(event.data.msg);

                            if (event.data.type == 'result')
                                return after_computation(event.data.result);
                        };

                        verifier_worker.postMessage({
                            'type': 'verify',
                            'election': raw_json,
                            'vote': encrypted_vote_json
                        });
                    } else {
                        var overall_result = verify_ballot(raw_json, encrypted_vote_json, result_append);
                        after_computation(overall_result);
                    }
                }, error: function () {
                    result_append('PROBLEM LOADING election. Are you sure you have the right election URL?<br />');

                    $('#loading').hide();

                    result_append('PROBLEM - THIS BALLOT DOES NOT VERIFY.');
                }
            });
        }
    }

}
</script>
