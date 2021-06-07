<template>
    <div v-if="election && picked_answers">

        <!-- breadcrumbs -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Elections</a></li>
                <li class="breadcrumb-item">
                    <router-link :to="{name:'election@view', params:{ slug: election.slug }}">{{
                            election.name
                        }}
                    </router-link>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Booth</li>
            </ol>
        </nav>

        <!-- voting wizard -->
        <form-wizard :title="election.name" subtitle="Voting booth" @on-complete="onComplete">

            <!-- questions -->
            <tab-content v-for="(question,q_idx) in election.questions" :key="q_idx" :title="'Question #' + (q_idx+1)">

                <b>{{ question.question }}</b>
                <MultipleChoice v-if="question.question_type ==='multiple_choice'" :question="question" :q_idx="q_idx"
                                v-model="picked_answers[question.id]"></MultipleChoice>
                <STV v-if="question.question_type ==='stv'" :question="question" :q_idx="q_idx"
                     v-model="picked_answers[question.id]"></STV>

            </tab-content>

            <!-- sealed, cast / audit -->
            <tab-content title="Cast / Audit"
                         :after-change="seal"
                         :before-change="cast">

                <VueObjectView :value="encrypted_vote"/>

                Sealed, cast / audit?
            </tab-content>

            <!-- done -->
            <tab-content title="Done">
                Done!

                <div class="row" v-for="trustee in election.trustees">
                    <country-flag
                        v-if="trustee.peer_server && trustee.accepts_ballots && trustee.peer_server.country_code"
                        :country='trustee.peer_server.country_code'/>
                </div>

            </tab-content>

            <!--            <button slot="prev">Back</button>-->
            <!--            <button slot="next">Next</button>-->
            <!--            <button slot="finish">Finish</button>-->

        </form-wizard>
    </div>
</template>

<script>

import {FormWizard, TabContent} from 'vue-form-wizard'
import Election from "../../../Models/Election";
import CastingModal from "./CastingModal";
import CountryFlag from 'vue-country-flag';
import VueObjectView from "vue-object-view";
import MultipleChoice from "./QuestionTypes/MultipleChoice";
import STV from "./QuestionTypes/STV";
import SmallJSONBallotEncoding from "../../../Voting/BallotEncodings/SmallJSONBallotEncoding";
import JSONBallotEncoding from "../../../Voting/BallotEncodings/JSONBallotEncoding";

export default {
    name: "NewBoothWizard",

    components: {
        STV,
        MultipleChoice,
        FormWizard,
        TabContent,
        CastingModal,
        CountryFlag,
        VueObjectView
    },

    data() {
        return {
            election: null,
            encrypted_vote: null,
            picked_answers: null
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
                    self.vote = JSON.stringify(self.election.questions.map(q => {
                        return [
                            1 // index of first answer
                        ];
                    }));

                    let _picked_answers = {};
                    self.election.questions.forEach(q => {
                        _picked_answers[q.id] = [];
                    });
                    self.picked_answers = _picked_answers;
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    console.log(e);
                });


            // var iconv = require('iconv-lite');
            // try {
            //     iconv.getCodec(); // if you get ANY named table here, then you won't except.
            // } catch (e) {
            //     // ignore
            //     console.log('ignored:', e);
            // }
            // console.log(iconv.encodings);
            // iconv.encodings['mycustommap'] = {
            //     type: '_sbcs',
            //     chars: '0123456789[],'
            // };
            // // test our two duplicate characters and the first PUA character
            // const buf = Buffer.from('[1,2,3]', 'ASCII');
            // const str = iconv.decode(buf, 'mycustommap');
            // const buf2 = iconv.encode(str, 'mycustommap');
            // console.log('src: ', buf);
            // console.log('string: ' + str + '');
            // const be = Buffer.from(str, 'utf16le').swap16();
            // console.log('string in utf16be: ', be);
            // console.log('back to big5: ', buf2);

            // let c = '[1,2,3]';
            // console.log(c);
            // let encoded = SmallJSONBallotEncoding.encodeStr(c);
            // console.log(encoded);
            // let decoded = SmallJSONBallotEncoding.decodeStr(encoded);
            // console.log(decoded);

        },


        seal() {
            let vote = [];
            Object.keys(this.picked_answers).forEach(k => {
                vote.push(this.picked_answers[k]);
            });
            console.log(vote);

            let vote_str = JSON.stringify(vote);

            console.log("############################################## JSONBallotEncoding");
            const voteInt = JSONBallotEncoding.encodeStr(vote_str);
            console.log(voteInt);
            console.log(JSON.parse(JSONBallotEncoding.decodeStr(voteInt)));

            console.log("############################################## SmallJSONBallotEncoding");
            const voteIntSM = SmallJSONBallotEncoding.encodeStr(vote_str);
            console.log(voteIntSM);
            console.log(JSON.parse(SmallJSONBallotEncoding.decodeStr(voteIntSM)));

            // encrypt vote
            let ptClass = this.election.getCryptoSystemClass().getPlainTextClass();
            // console.log(ptClass);

            /** @type {EGPlaintext|RSAPlaintext} */
            let p = new ptClass(voteInt, this.election.public_key);
            // console.log(p);

            this.encrypted_vote = p.encrypt().toJSONObject();
            console.log(this.encrypted_vote);

            return true;
        },

        cast() {
            let self = this;
            this.election.trustees.filter(trustee => {
                return trustee.peer_server && trustee.accepts_ballots;
            }).forEach(trustee => {
                axios.post("https://" + trustee.peer_server.domain + '/api/elections/' + this.election.slug + '/cast', {
                    vote: this.encrypted_vote
                })
                    .then(response => {
                        self.$toastr.success("OK " + trustee.peer_server.domain);
                    })
                    .catch(e => {
                        self.$toastr.error("Error " + trustee.peer_server.domain);
                    });
            });
            return true;
        },

        onComplete() {
        }
    }
}
</script>

<style>
@import 'vue-form-wizard/dist/vue-form-wizard.min.css';

.wizard-progress-with-circle {
    background: #f3f2ee !important;
}
</style>
