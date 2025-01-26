<template>
    <div>

        <div v-if="election.use_advanced_audit_features" id="use_advanced_audit_features">
            <h4>
                <a onclick="$('#auditbody').slideToggle(250);" href="javascript:void(0)">Audit</a>
                <span style="font-size: 0.8em; color: #444">[optional]</span>
            </h4>
            <div id="auditbody" style="display:none;">
                <p>
                    If you choose, you can audit your ballot and reveal how your choices were encrypted.
                </p>
                <p>
                    You will then be guided to re-encrypt your choices for final casting.
                </p>
                <input type="button" value="Verify Encryption" onclick="BOOTH.audit_ballot();" class="pretty"/>
            </div>
        </div>

        <h3>Review your Ballot</h3>

        <div
            style="padding: 10px; margin-bottom: 10px; background-color: #eee; border: 1px #ddd solid; max-width: 340px;">
            <div v-for="(question,idx) in election.questions">
                <b>Question #{{ idx + 1 }}: {{ question.slug }}</b><br>

                <div v-if="choices[idx].length == 0" style="margin-left: 15px;">&#x2610; <i>No choice selected</i></div>

                <div v-for="choice in choices[idx]" style="margin-left: 15px;">&#x2713; {{ choice }}</div>

                <div v-if="choices[idx].length < question.max">
                    [you selected {{ choices[idx].length }} candidates; you may select from {{ question.min }} to
                    {{ question.max }}]
                </div>

                [<a @click="show_question(idx)" href="javascript:void(0)">edit responses</a>]
                <!--        {#if !$T.question$last}{#/if}--><br><br>
            </div>
        </div>

        <p>
            Your ballot tracker is <b><tt style="font-size: 11pt;">{{ encrypted_vote_hash }}</tt></b>, and you can
            <a @click="show_receipt" href="javascript:void(0)">print</a> it.
            <br/><br/>
        </p>

        <p>
            Once you click "Submit", the unencrypted version of your ballot will be destroyed, and only the encrypted
            version will remain. The encrypted version will be submitted to the Helios server.
        </p>

        <button :disabled="!can_proceed" @click="cast_ballot">Submit this Vote!</button>
        <br/>
        <div v-show="show_loading"><img src="/assets/img/loading.gif" id="proceed_loading_img"/></div>

        <div class="prettyform">
            <textarea v-show="show_encrypted_vote" name="encrypted_vote" v-model="encrypted_vote_json">
            </textarea>
        </div>

    </div>
</template>

<script>

export default {
    name: "Seal",

    props: {
        election: {}
    },

    data() {
        return {
            encrypted_vote_hash: '', // TODO
            encrypted_vote_json: '', // TODO probably should be a prop,
            election_hash: '', // TODO probably should be a prop,
            show_encrypted_vote: false,
            show_loading: true,
            can_proceed: false,
        }
    },

    methods: {

        show_question(idx) {
            // TODO
        },

        show_receipt() {
            // TODO
        },

        cast_ballot() {
            if (!this.can_proceed) {
                return;
            }

            this.show_loading = true; // show progress spinner
            this.can_proceed = false;

            // at this point, we delete the plaintexts by resetting the ballot
            this.setup_ballot(this.election);

            // clear the plaintext from the encrypted
            if (this.encrypted_ballot) {
                this.encrypted_ballot.clearPlaintexts();
            }

            this.encrypted_ballot_serialized = null;
            this.encrypted_ballot_with_plaintexts_serialized = null;

            // remove audit trail
            this.audit_trail = null;

            // we're ready to leave the site
            this.started_p = false;

            // submit the form
            let self = this;
            this.$http.post(BASE_URL + '/api/elections/' +  this.election.slug + '/cast_confirm', {
                encrypted_vote: this.encrypted_vote_json,
                election_hash: this.election_hash
            })
                .then(response => {
                    self.$toastr.success("OK")
                })
                .catch(e => {
                    self.$toastr.error("Error")
                });
        },
    }
}
</script>

<style scoped>
#use_advanced_audit_features {
    float: right;
    background: lightyellow;
    margin-left: 20px;
    padding: 0px 10px 10px 10px;
    border: 1px solid #ddd;
    width: 200px;
}
</style>
