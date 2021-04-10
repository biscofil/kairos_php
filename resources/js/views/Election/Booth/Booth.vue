<template>

    <div v-if="election">

        <Header :election="election"/>

        <div id="page">
            <div id="progress_div" v-show="show_progress_div" style="display:none; width: 500px; margin:auto;">
                <table width="100%">
                    <tr>
                        <td id="progress_1">(1) Select</td>
                        <td id="progress_2">(2) Review</td>
                        <td id="progress_3">(3) Submit</td>
                    </tr>
                </table>
            </div>

            <div id="election_div" class="panel">

                <BoothElection v-if="show_election_component" :election="election"
                               @ok="show_question(1)"></BoothElection>

                <div v-else>
                    <h3>Checking capabilities and loading election booth...</h3>
                    <div align="center">
                        <img src="loading.gif"/>
                        <br/>This may take up to 10 seconds
                    </div>
                </div>

            </div>

            <div id="error_div" class="panel" style="display: none;">
                <h3>There's a problem</h3>
                <p>
                    It appears that your browser does not have Java enabled. Helios needs Java to perform encryption
                    within the
                    browser.
                </p>
                <p>
                    You may be able to install Java by visiting <a target="_new" href="http://java.com">java.com</a>.
                </p>
            </div>

            <div id="question_div" class="panel">
                <Question :question="current_question" :warning_box_text="warning_box_text"></Question>
            </div>

            <div id="processing_div" class="panel" style="display:none;">
                <h3 align="center">Processing....</h3>
            </div>

            <div id="encrypting_div" class="panel" style="display:none;">
                <h3 align="center">Helios is now encrypting your ballot<br/>
                    <img src="encrypting.gif"/> <span style="font-size:0.7em; display:none;"
                                                      id="percent_done_container">
            (<span id="percent_done">0</span>%)</span>
                </h3>
                <p align="center"><b>This may take up to two minutes.</b></p>
            </div>

            <div id="seal_div" class="panel">
                <Seal :election="election"></Seal>
            </div>

            <div id="audit_div" class="panel">
                <Audit :election="election"></Audit>
            </div>

        </div>

        <br clear="both"/>

        <Footer :election="election"/>

    </div>

</template>

<script>

import Footer from "./Footer";
import Header from "./Header";
// TODO import Election from "../../../helios/Election";
import Question from "./Question";
import BoothElection from "./BoothElection";
import Seal from "./Seal";
import Audit from "./Audit";
import Utils from "../../../Models/Utils/Utils";
import EncryptedVote from "../../../Models/EncryptedVote";
import Progress from "../../../Models/Utils/Progress";

const {SHA256} = require("sha2");

export default {
    name: "Booth",

    components: {
        Audit,
        Seal,
        BoothElection,
        Question,
        Header,
        Footer,
    },

    data() {
        return {
            election: null,
            questions: null,
            answers: null,
            //
            started_p: false,
            total_cycles_waited: 0,
            ready_p: false,
            synchronous: false,
            //
            show_progress_div: true,
            warning_box_text: null,
            current_question: null,
            show_election_component: false
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_election(slug);

        document.addEventListener('beforeunload', this.onBeforeUnloadHandler)

        // we're asynchronous if we have SJCL and Worker
        this.synchronous = true; //!(USE_SJCL && window.Worker);

    },

    watch: {
        $route(to, from) {
            this.fetch_election(to.params.slug);
        }
    },

    methods: {

        fetch_election(slug) {

            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + slug)
                .then(response => {

                    // self.questions = JSON.parse(response.data.questions);

                    self.answers = [];
                    Array.prototype.forEach.call(response.data.questions, question => {
                        self.answers.push([]);
                    });

                    // TODO self.election = Election.fromJSONObject(response.data);

                    self.so_lets_go();

                })
                .catch(e => {
                    console.log(e);
                    self.$toastr.error("Error")
                });
        },

        // ################################################################################################
        // ################################################################################################
        // ################################################################################################

        onBeforeUnloadHandler(evt) {
            if (!this.started_p)
                return;
            if (typeof evt == 'undefined') {
                evt = window.event;
            }
            let message = "If you leave this page with an in-progress ballot, your ballot will be lost.";
            if (evt) {
                evt.returnValue = message;
            }
            return message;
        },

        close() {
            if (confirm("Are you sure you want to exit the booth and lose all information about your current ballot?")) {
                this.started_p = false;
                console.log("TODO close");
                // TODO window.location = this.election.cast_url;
            }
        },


        setup_templates() {
            // TODO
            // $('#seal_div').setTemplateURL("templates/seal.html" );
            // $('#audit_div').setTemplateURL("templates/audit.html" );
        },

        setup_ballot(election) {
            this.ballot = {};

            // dirty markers for encryption (mostly for async)
            this.dirty = [];

            // each question starts out with an empty array answer
            // and a dirty bit to make sure we encrypt
            this.ballot.answers = [];
            this.election.questions.forEach(function (i, x) {
                this.ballot.answers[i] = [];
                this.dirty[i] = true;
            });
        },

        // all ciphertexts to null
        reset_ciphertexts() {
            this.encrypted_answers.forEach(function (enc_answer, ea_num) {
                this.launch_async_encryption_answer(ea_num);
            });
        },

        setup_workers(election_raw_json) {
            // async?
            if (!this.synchronous) {
                // prepare spots for encrypted answers
                // and one worker per question
                this.encrypted_answers = [];
                this.answer_timestamps = [];
                this.worker = new window.Worker("boothworker-single.js");
                this.worker.postMessage({
                    'type': 'setup',
                    'election': election_raw_json
                });

                this.worker.onmessage = function (event) {
                    // logging
                    if (event.data.type == 'log')
                        return console.log(event.data.msg);

                    // result of encryption
                    if (event.data.type == 'result') {
                        // this check ensures that race conditions
                        // don't screw up votes.
                        if (event.data.id == this.answer_timestamps[event.data.q_num]) {
                            this.encrypted_answers[event.data.q_num] = HELIOS.EncryptedAnswer.fromJSONObject(event.data.encrypted_answer, this.election);
                            console.log("got encrypted answer " + event.data.q_num);
                        } else {
                            console.log("no way jose");
                        }
                    }
                };

                this.election.questions.forEach(function (q_num, q) {
                    this.encrypted_answers[q_num] = null;
                });
            }
        },

        escape_html(content) { // TODO remove
            return content; // $('<div/>').text(content).html();
        },

        setup_election() {

            // async?
            // TODO this.setup_workers(raw_json);

            // whether the election wants candidate order randomization or not
            // we set up an ordering array so that the rest of the code is
            // less error-prone.
            this.election.question_answer_orderings = [];
            this.election.questions.forEach(function (i, question) {
                let ordering = new Array(question.answers.length);

                // initialize array so it is the identity permutation
                ordering.forEach(function (j, answer) {
                    ordering[j] = j;
                });

                // if we want reordering, then we shuffle the array
                if (this.election.election && this.election.election.randomize_answer_order) {
                    shuffleArray(ordering); // TODO
                }

                this.election.question_answer_orderings[i] = ordering;
            });

            /**
             $('#seal_div').setTemplateURL("templates/seal.html" + cache_bust);
             $('#audit_div').setTemplateURL("templates/audit.html" + cache_bust);
             */

            this.setup_ballot();
        },

        show(el) {
            $('.panel').hide();
            el.show();
            return el;
        },

        show_election() {
            //this.show($('#election_div')).processTemplate({'election': this.election});
            this.show_election_component = true;
        },

        launch_async_encryption_answer(question_num) {
            this.answer_timestamps[question_num] = new Date().getTime();
            this.encrypted_answers[question_num] = null;
            this.dirty[question_num] = false;
            this.worker.postMessage({
                'type': 'encrypt',
                'q_num': question_num,
                'answer': this.ballot.answers[question_num],
                'id': this.answer_timestamps[question_num]
            });
        },

        // check if the current question is ok
        validate_question(question_num) {
            // check if enough answers are checked
            if (this.ballot.answers[question_num].length < this.election.questions[question_num].min) {
                alert('You need to select at least ' + this.election.questions[question_num].min + ' answer(s).');
                return false;
            }

            // if we need to launch the worker, let's do it
            if (!this.synchronous) {
                // we need a unique ID for this to ensure that old results
                // don't mess things up. Using timestamp.
                // check if dirty
                if (this.dirty[question_num]) {
                    this.launch_async_encryption_answer(question_num);
                }
            }
            return true;
        },

        validate_and_confirm(question_num) {
            if (this.validate_question(question_num)) {
                this.seal_ballot();
            }
        },

        next(question_num) {
            if (this.validate_question(question_num)) {
                this.show_question(question_num + 1);
            }
        },

        previous(question_num) {
            if (this.validate_question(question_num)) {
                this.show_question(question_num - 1);
            }
        },

        // http://stackoverflow.com/questions/2450954/how-to-randomize-shuffle-a-javascript-array
        shuffleArray(array) {
            let currentIndex = array.length
                , temporaryValue
                , randomIndex;

            // While there remain elements to shuffle...
            while (0 !== currentIndex) {
                // Pick a remaining element...
                randomIndex = Math.floor(Math.random() * currentIndex);
                currentIndex -= 1;

                // And swap it with the current element.
                temporaryValue = array[currentIndex];
                array[currentIndex] = array[randomIndex];
                array[randomIndex] = temporaryValue;
            }

            return array;
        },

        show_question(question_num) {
            this.started_p = true;

            // the first time we hit the last question, we enable the review all button
            if (question_num === this.election.questions.length - 1) {
                this.all_questions_seen = true;
            }

            this.show_progress('1');

            this.current_question = {
                'question_num': question_num,
                'last_question_num': this.election.questions.length - 1,
                'question': this.election.questions[question_num], 'show_reviewall': this.all_questions_seen,
                'answer_ordering': this.election.question_answer_orderings[question_num]
            };

            // fake clicking through the answers, to trigger the disabling if need be
            // first we remove the answers array
            let answer_array = this.ballot.answers[question_num];
            this.ballot.answers[question_num] = [];

            // we should not set the dirty bit here, so we save it away
            let old_dirty = this.dirty[question_num];
            answer_array.forEach(function (i, ans) {
                this.click_checkbox_script(question_num, ans, true);
            });
            this.dirty[question_num] = old_dirty;
        },

        click_checkbox_script(question_num, answer_num) {
            document.forms['answer_form']['answer_' + question_num + '_' + answer_num].click();
        },

        click_checkbox(question_num, answer_num, checked_p) {
            // keep track of dirty answers that need encrypting
            this.dirty[question_num] = true;

            if (checked_p) {
                // multiple click events shouldn't screw this up
                if (this.ballot.answers[question_num].indexOf(answer_num) === -1) {
                    this.ballot.answers[question_num].push(answer_num);
                }
                $('#answer_label_' + question_num + "_" + answer_num).addClass('selected');
            } else {
                this.ballot.answers[question_num] = Utils.array_remove_value(this.ballot.answers[question_num], answer_num);
                $('#answer_label_' + question_num + "_" + answer_num).removeClass('selected');
            }

            if (this.election.questions[question_num].max != null && this.ballot.answers[question_num].length >= this.election.questions[question_num].max) {
                // disable the other checkboxes
                $('.ballot_answer').each(function (i, checkbox) {
                    if (!checkbox.checked)
                        checkbox.disabled = true;
                });

                // do the warning only if the question allows more than one option, otherwise it's confusing
                if (this.election.questions[question_num].max > 1) {
                    this.warning_box_text = "Maximum number of options selected.<br />To change your selection, please de-select a current selection first.";
                }
            } else {
                // enable the other checkboxes
                $('.ballot_answer').each(function (i, checkbox) {
                    checkbox.disabled = false;
                });
                this.warning_box_text = null;
            }
        },

        show_processing_before(str_to_execute) {
            $('#processing_div').html("<h3 align='center'>Processing...</h3>");
            this.show($('#processing_div'));

            // add a timeout so browsers like Safari actually display the processing message
            setTimeout(str_to_execute, 250);
        },

        show_encryption_message_before(func_to_execute) {
            this.show_progress('2');
            this.show($('#encrypting_div'));

            func_to_execute();
        },

        hide_progress() {
            this.show_progress_div = false;
        },

        show_progress(step_num) {
            this.show_progress_div = true;
            ['1', '2', '3', '4'].forEach(function (n, step) {
                // TODO
                if (step == step_num) {
                    $('#progress_' + step).attr('class', 'selected');
                } else {
                    $('#progress_' + step).attr('class', 'unselected');
                }
            });
        },

        so_lets_go() {
            this.hide_progress();
            this.setup_templates();

            this.setup_election();
            this.show_election();
        },

        check_encryption_status() {
            var progress = this.progress.progress();
            if (progress == "" || progress == null) {
                progress = "0";
            }

            $('#percent_done').html(progress);
        },

        _after_ballot_encryption() {
            // if already serialized, use that, otherwise serialize
            this.encrypted_vote_json = this.encrypted_ballot_serialized || JSON.stringify(this.encrypted_ballot.toJSONObject());

            var do_hash = function () {
                this.encrypted_ballot_hash = SHA256(this.encrypted_vote_json).toString("base64"); // this.encrypted_ballot.get_hash();
                window.setTimeout(show_cast, 0);
            };

            var show_cast = function () {
                $('#seal_div').processTemplate({
                    // TODO
                    'cast_url': this.election.cast_url,
                    'encrypted_vote_json': this.encrypted_vote_json,
                    'encrypted_vote_hash': this.encrypted_ballot_hash,
                    'election_slug': this.election.slug,
                    'election_hash': this.election_hash,
                    'election': this.election,
                    'questions': this.election.questions,
                    'choices': BALLOT.pretty_choices(this.election, this.ballot)
                });
                this.show($('#seal_div'));
                this.encrypted_vote_json = null;
            };

            window.setTimeout(do_hash, 0);
        },

        // wait for all workers to be done
        wait_for_ciphertexts() {
            this.total_cycles_waited += 1;

            var answers_done = _.reject(this.encrypted_answers, _.isNull);
            var percentage_done = Math.round((100 * answers_done.length) / this.encrypted_answers.length);

            if (this.total_cycles_waited > 250) {
                alert('there appears to be a problem with the encryption process.\nPlease email help@heliosvoting.org and indicate that your encryption process froze at ' + percentage_done + '%');
                return;
            }

            if (percentage_done < 100) {
                setTimeout(this.wait_for_ciphertexts, 500);
                $('#percent_done').html(percentage_done + '');
                return;
            }

            this.encrypted_ballot = EncryptedVote.fromEncryptedAnswers(this.election, this.encrypted_answers);

            this._after_ballot_encryption();
        },

        // TODO check here
        seal_ballot_raw() {
            if (this.synchronous) {
                this.progress = new Progress();
                var progress_interval = setInterval("this.check_encryption_status()", 500);
                this.encrypted_ballot = new EncryptedVote(this.election, this.ballot.answers, this.progress);
                clearInterval(progress_interval);
                this._after_ballot_encryption();
            } else {
                this.total_cycles_waited = 0;
                this.wait_for_ciphertexts();
            }
        },

        request_ballot_encryption() {
            this.$http.post(BASE_URL + "/api/elections/" + this.election.slug + "/encrypt-ballot", {
                'answers_json': JSON.stringify(this.ballot.answers)
            })
                .then(response => {
                    let result = response.data;
                    //this.encrypted_ballot = HELIOS.EncryptedVote.fromJSONObject($.secureEvalJSON(result), this.election);
                    // rather than deserialize and reserialize, which is inherently slow on browsers
                    // that already need to do network requests, just remove the plaintexts

                    this.encrypted_ballot_with_plaintexts_serialized = result;
                    let ballot_json_obj = JSON.parse(this.encrypted_ballot_with_plaintexts_serialized);
                    let answers = ballot_json_obj.answers;
                    for (var i = 0; i < answers.length; i++) {
                        delete answers[i]['answer'];
                        delete answers[i]['randomness'];
                    }

                    this.encrypted_ballot_serialized = JSON.stringify(ballot_json_obj);

                    window.setTimeout(this._after_ballot_encryption, 0);
                });
        },

        seal_ballot() {
            this.show_progress('2');

            // if we don't have the ability to do crypto in the browser,
            // we use the server
            if (!BigInt.in_browser) {
                this.show_encryption_message_before(this.request_ballot_encryption, true);
            } else {
                this.show_encryption_message_before(this.seal_ballot_raw, true);
                $('#percent_done_container').show();
            }
        },

        audit_ballot() {
            this.audit_trail = this.encrypted_ballot_with_plaintexts_serialized || JSON.stringify(this.encrypted_ballot.get_audit_trail());

            this.show($('#audit_div')).processTemplate({
                'audit_trail': this.audit_trail,
                'election_url': BASE_URL + "/api/elections/" + this.election.slug
            });
        },

        post_audited_ballot() {
            this.$http.post(BASE_URL + "/api/elections/" + this.election.slug + "/post-audited-ballot", {
                'audited_ballot': this.audit_trail
            })
                .then(response => {
                    let result = response.data;
                    alert('This audited ballot has been posted.\nRemember, this vote will only be used ' +
                        'for auditing and will not be tallied.\nClick "back to voting" and cast a ' +
                        'new ballot to make sure your vote counts.');
                });
        },

        show_receipt() {
            const mime_type = "text/plain";
            const content = "Your smart ballot tracker for " + this.election.name + ": " + this.encrypted_ballot_hash;
            const is_ie = false; // TODO check
            if (is_ie) {
                w = window.open("");
                w.document.open(mime_type);
                w.document.write(content);
                w.document.close();
            } else {
                w = window.open("data:" + mime_type + "," + encodeURIComponent(content));
            }

        },

        do_done() {
            this.started_p = false;
        },

    }

}
</script>

<style scoped>

body {
    font-family: Arial;
    background: white;
    padding: 0px;
    margin: 0px;
}

#wrapper {
    position: absolute;
    padding: 0px;
    background: white;
    border: 1px solid #666;
    top: 20px;
    margin-left: 100px;
    margin-top: 0px;
    width: 1000px;
}

#content {
    padding: 20px 30px 20px 30px;
}

#header {
    padding-top: 0px;
    text-align: center;
    padding-bottom: 5px;
}

#header h1 {
    font-size: 28pt;
    padding: 0px;
    margin: 0px;
    line-height: 120%;
}

#header h2 {
    font-size: 20pt;
    padding: 0px;
    margin: 0px;
    line-height: 100%;
    font-weight: none;
}

#banner {
    width: 100%;
    text-align: center;
    padding: 2px 0px 2px 0px;
    background: #ccc;
    font-size: 18pt;
    border-bottom: 1px solid #666;
}

#progress_div {
    width: 100%;
    font-size: 14pt;
}

#progress_div table {
    border-collapse: collapse;
    text-align: center;
    border: 0px;
}

#progress_div td.unselected {
    background: #ccc;
    color: #888;
    border: 1px solid #333;
}

#progress_div td.selected {
    background: #fc9;
    color: black;
    font-weight: bold;
    border: 1px solid black;
}

#footer {
    position: relative;
    bottom: 0px;
    width: 100%;
    text-align: center;
    margin-top: 10px;
    padding: 2px 0px 2px 0px;
    background: #ddd;
    border-top: 1px solid #666;
}

#page h2 {
    background: #fc9;
    border-bottom: 1px solid #f90;
    padding: 5px 0px 2px 5px;
}

h3 {
    font-size: 1.6em;
}

#election_info {
    font-size: 16pt;
}

#election_hash {
    font-family: courier;
}

#loading_div {
    display: none;
}
</style>
