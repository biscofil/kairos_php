<template>
    <div>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Elections</a></li>
                <li class="breadcrumb-item">
                    <router-link :to="{name:'election@view', params:{ slug: election.slug }}" >{{ election.name }}</router-link>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Booth</li>
            </ol>
        </nav>

        Vote:
        <input type="text" class="form-control" v-model="vote">
        <button class="btn btn-success" @click="submit">OK</button>

        <form-wizard title="" subtitle="" v-if="false"> <!-- TODO show -->
            <tab-content v-for="(question,idx) in questions" :key="idx" :title="'Question #' + (idx+1)">
                <b>{{ question.question }}</b>
                <span>You can pick {{ question.min }} to {{ question.max }} answers</span>
                <div v-for="answer in question.answers">
                    <label>
                        {{ answer }}
                        <input type="checkbox" v-model="answers[idx]" :value="answer"
                               :disabled="answers[idx].length >= question.max && answers[idx].indexOf(answer) === -1">
                    </label>
                </div>
            </tab-content>

            <tab-content title="Review">
                Encrypted
            </tab-content>
            <tab-content title="Submit">
                Yuhuuu! This seems pretty damn simple
            </tab-content>
        </form-wizard>
    </div>
</template>

<script>

import {FormWizard, TabContent} from 'vue-form-wizard'
import 'vue-form-wizard/dist/vue-form-wizard.min.css'
import Election from "../../../Models/Election";
import EGPlaintext from "../../../Voting/CryptoSystems/Elgamal/EGPlaintext";

export default {
    name: "NewBoothWizard",

    components: {
        FormWizard,
        TabContent
    },

    data() {
        return {
            election: null,
            vote: "[1,3,4,[3,5]]"
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_election(slug);
    },

    watch: {
        $route(to, from) {
            this.fetch_election(to.params.slug);
        }
    },

    methods: {
        fetch_election(slug) {
            let self = this;
            Election.fetch(slug)
                .then(election => {
                    self.election = election;
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    console.log(e);
                });
        },

        submit() {
            const v = EGPlaintext.getBigIntFromDict(JSON.parse(this.vote));
            console.log(v);
            // encrypt vote
            let p = new EGPlaintext(v, this.election.public_key, false);
            let c = p.encrypt();
            //let k = election.private_key.decrypt(c);
            //console.log(k);
            let self = this;
            this.election.trustees.forEach(trustee => {
                axios.post("https://" + trustee.peer_server.domain + '/api/elections/' + this.election.slug + '/cast', {
                    vote: c.toJSONObject()
                })
                    .then(response => {
                        self.$toastr.success("OK " + trustee.peer_server.domain);
                    })
                    .catch(e => {
                        self.$toastr.error("Error " + trustee.peer_server.domain);
                    });
            });

        }
    }
}
</script>

<style scoped>

</style>
