<template>
    <div v-if="trustee">
        <h2 class="title">{{ election.name }} <span> &mdash; Trustee {{ trustee.name }} Home</span></h2>

        <div> <!-- public_key_hash -->
            <p v-if="trustee.public_key_hash">
                You have successfully uploaded your public key.<br/>
                Your public key fingerprint is: <b>{{ trustee.public_key_hash }}</b>.<br/>
                You can <a href="javascript:void(0)" @click="check_sk">verify that you have the right secret key</a>.
            </p>

            <p v-else>
                <a href="javascript:void(0)" @click="setup_key">setup your key</a>
            </p>
        </div>

        <div> <!-- encrypted_tally -->
            <div v-if="election.encrypted_tally">
                <span v-if="trustee.decryption_factors"> You have successfully uploaded your decryption.</span>
                <p v-else>
                    The encrypted tally for this election is ready.<br/>
                    <a href="javascript:void(0)" @click="decrypt_and_prove">decrypt with your key</a>
                </p>
            </div>
            <p v-else>
                Once the tally is computed, come back here to provide your secret key for decryption purposes.<br/>
                You should keep the email with your trustee homepage link, which contains the credentials needed to get
                back
                here.
            </p>
        </div>
    </div>
</template>

<script>
import KeyGenerator from "../../../components/KeyGenerator";
import TrusteeCheckSKModal from "../../../components/TrusteeCheckSKModal";
import {EventBus} from "../../../event-bus";
import Election from "../../../Models/Election";

export default {
    name: "TrusteeHome",

    data() {
        return {
            election: null,
            trustee: null,
        }
    },

    watch: {
        $route(to, from) {
            this.fetch_trustee_election(to.params.slug);
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_trustee_election(slug);
    },

    methods: {

        fetch_trustee_election(slug) {
            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + slug + '/trustee/home')
                .then(response => {
                    self.election = Election.fromJSONObject(response.data.election);
                    self.trustee = response.data.trustee;
                })
                .catch(e => {
                    console.log(e);
                    self.$toastr.error("Error");
                });
        },

        setup_key() {
            //TODO election@trustee@key-generator election.slug trustee.slug %}

            let self = this;

            EventBus.$on('addedTrustee', function (trustees) {
              self.trustees = trustees;
            });

            this.$modal.show(KeyGenerator, {
                election: this.election,
                trustee: this.trustee
            }, {
                height: 'auto'
            });

        },

        check_sk() {
            //TODO election@trustee@check-sk election.slug trustee.slug %}

            let self = this;

            EventBus.$on('addedTrustee', function (trustees) {
              //self.trustees = trustees;
            });

            this.$modal.show(TrusteeCheckSKModal, {
                election: this.election
            }, {
                height: 'auto'
            });

        },

        decrypt_and_prove() {
            //TODO election@trustee@decrypt-and-prove election.slug trustee.slug %}
        }

    }

}
</script>
