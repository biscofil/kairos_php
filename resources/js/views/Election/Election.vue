<template>

    <div>
        <div v-if="election == null">
            Loading election...
        </div>
        <div v-else>
            <div style="float: left; margin-right: 50px;">

                <h3 class="title">{{ election.name }}
                    <small v-if="election.is_auth_user_admin && !election.frozen_at">
                        <router-link class="small button" :to="'/elections/' + election.slug + '/edit'">
                            Edit
                        </router-link>
                    </small>
                </h3>

                <div style="padding-top:0px; margin-top:0px">
                    <span v-if="election.is_private">private</span>
                    <span v-else>public</span>

                    {{ election.election_type }}

                    <span v-if="$root.settings.SHOW_USER_INFO && election.admin">
                        Created by <u><b>{{ election.admin_name }}</b></u>
                    </span>

                    <span v-if="election.archived_at">[Archived]</span>

                    <div v-if="election.is_auth_user_admin">
                        <a v-if="election.archived_at" class="small button" href="javascript:void(0)"
                           @click="set_archived(false)">
                            Unarchive it
                        </a>
                        <a v-else class="small button" href="javascript:void(0)" @click="set_archived(true)">
                            Archive it
                        </a>
                        <a class="small button" @click="copy_election" href="javascript:void(0)">copy</a>
                    </div>

                    <br/>

                    <div v-if="election.is_auth_user_admin && !election.is_private">
                        <div v-if="election.featured_at">
                            this {{ election.election_type }} is featured on the front page.
                            <a v-if="can_feature_p" href="javascript:void(0)" @click="set_featured(false)"
                               class="brackets_around">Unfeature it</a>
                        </div>
                        <div v-else>
                            this {{ election.election_type }} is <u>not</u> featured on the front page.
                            <a v-if="can_feature_p" href="javascript:void(0)" @click="set_featured(true)"
                               class="brackets_around">Feature it</a>
                        </div>
                    </div>

                    <div v-if="election.is_auth_user_trustee">
                        You are a trustee for this election.
                        <router-link :to="{ name: 'election@trustee', params: { slug: election.slug }}"
                                     class="small button">
                            Go to your trustee home
                        </router-link>
                    </div>

                </div>

                <br/>
                <br clear="left"/>

                <p style="margin-bottom: 25px; line-height: 1.3;">
                    {{ election.description }}
                </p>

                <p v-if="election.info_url" style="font-size:1.5em;">
                    [<a target="_blank" :href="election.info_url" rel="noopener noreferrer" class="brac">
                    download candidate bios &amp; statements
                </a>]
                </p>

                <p align="center" style="font-size: 1.5em;">
                    <router-link :to="{ name: 'election@questions', params: { slug: election.slug }}">
                        questions ({{ election.questions ? election.questions.length : "0" }})
                    </router-link>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <router-link :to="{ name: 'election@voters@list-pretty', params: { slug: election.slug }}">
                        voters &amp; ballots
                    </router-link>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    <router-link :to="{ name: 'election@trustees@view', params: { slug: election.slug }}">
                        trustees ({{ election.trustee_count }})
                    </router-link>
                </p>

                <!-- OK SO FAR -->

                <div v-if="election.is_auth_user_admin">
                    <div v-if="election.frozen_at">
                        <div
                            style="background: lightyellow; padding:5px; padding-left: 10px; margin-top: 15px; border: 1px solid #aaa; width: 720px;"
                            class="round">
                            <a href="javascript:void(0)" @click="show_badge_body =! show_badge_body">
                                Embed an Election Badge
                            </a>
                            <div v-show="show_badge_body">
                                <br/>
                                <form action="#">
                                    <textarea cols="90" rows="4" wrap="soft">
                                      <iframe :src="election_badge_url" frameborder="0" style="border: 1px solid black" height="75" width="200">
                                        </iframe>
                                    </textarea>
                                    <br/>
                                    <p style="font-size:0.8em;"> adding this HTML to your site displays a thin banner
                                        with direct
                                        links to voting.</p>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div v-if="election.result_released_at">
                        <!-- election complete, no next step -->
                    </div>

                    <div v-else>

                        <b>Next Step:</b>
                        <div style="font-size: 1.3em;">

                            <div v-if="!election.frozen_at">
                                <div v-if="election.issues_before_freeze">
                                    <span v-for="issue in election.issues_before_freeze">
                                      {{ issue.action }},
                                    </span>
                                </div>
                                <div v-else>
                                    <a href="javascript:void(0)" @click="freeze">freeze ballot and open election.</a>
                                    <br/>
                                    <div v-if="election.voting_starts_at">
                                        once you do this, the election will be ready for voting and will open
                                        automatically<br/>
                                        at {{ election.voting_starts_at | moment("MM/DD/YYYY HH:mm:ss") }}, as per your
                                        settings.
                                    </div>
                                    <div v-else>
                                        once you do this, the election will be immediately open for voting.
                                    </div>
                                </div>
                            </div>

                            <div v-else><!-- not frozen -->

                                <div v-if="!election.encrypted_tally">
                                    <div v-if="election.tallying_started_at">
                                        Tally computation is under way.<br/>
                                        Reload this page in a couple of minutes.
                                    </div>
                                    <div v-else>
                                        <a href="election@compute-tally election.slug">compute encrypted tally</a><br/>
                                        The encrypted votes will be combined into an encrypted tally. Once this is done,<br/>
                                        trustees will be asked to provide their share of the decryption.
                                    </div>
                                </div>
                                <div v-else>

                                    <div v-if="election.result">
                                        <a href="election@release-result election.slug">release result</a><br/>
                                        The result displayed below is visible only to you.<br/>
                                        Once you release the result, it will be visible to everyone.
                                    </div>
                                    <div v-else>
                                        <div v-if="election.ready_for_decryption_combination">
                                            <a href="election@combine-decryptions election.slug">
                                                <span v-if="election.trustee_count == 1">compute results</span>
                                                <span v-else>combine trustee decryptions and compute results</span>
                                            </a>
                                            <br/>
                                            <div v-if="election.trustee_count == 1">
                                                The result will be computed and shown to you, the administrator, only.
                                            </div>
                                            <div v-else>
                                                The decryption shares from the trustees will be combined and the tally
                                                computed.<br/>
                                                Once you do this, the tally will visible to you, the administrator,
                                                only.
                                            </div>
                                        </div>
                                        <div v-else>
                                            <a href="election@trustees@view election.slug">trustees (for decryption)</a>
                                        </div>
                                    </div>

                                </div>

                            </div>
                        </div>

                    </div>

                </div>

                <br/>

                <div v-if="show_result">
                    <div v-if="election.result_released_at">
                        <span class="highlight-box round">
                          This election is complete.
                        </span>
                        <br/><br/><br/>
                    </div>

                    <h3 class="highlight-box">Tally</h3>
                    <div v-for="(question, index) in election.pretty_result">
                        <b><span style="font-size:0.8em;">Question #{{ index }}</span><br/>{{ question.question }}</b>
                        <br/>
                        <table class="pretty" style="width: auto;">
                            <tr v-for="answer in question.answers">

                                <!--            <td style="padding-right:80px;<div v-if="answer.winner">font-weight:bold;</div>">{{ answer.answer }}</td>-->
                                <!--            <td align="right" style="<div v-if="answer.winner">font-weight:bold;</div>">{{ answer.count }}</td> -->

                                <td style="padding-right:80px;">{{ answer.answer }}</td>
                                <td align="right" style="font-weight:bold">{{ answer.count }}</td>
                            </tr>
                        </table>
                    </div>

                </div>

                <div v-else>
                    <div v-if="election.voting_has_stopped">
                        <span class="highlight-box round">
                          Election closed. Results will be released soon.
                        </span>
                        <br/><br/>
                    </div>

                    <div v-else>
                        <div v-if="election.voting_started_at">
                            <router-link :to="{name: 'election@vote', params: { slug: election.slug }}">
                                <button class="button"> Vote in this {{ election.election_type }}</button>
                            </router-link>
                            <br/>
                            <br/>

                            <div v-if="election.voting_extended_until">
                                This {{ election.election_type }} was initially scheduled to end at
                                {{ election.voting_ends_at | moment("MM/DD/YYYY HH:mm:ss") }}
                                (UTC),<br/>
                                but has been extended until
                                {{ election.voting_extended_until | moment("MM/DD/YYYY HH:mm:ss") }} (UTC).
                            </div>

                            <div v-else>
                                <div v-if="election.voting_ends_at">
                                    <br/>
                                    This {{ election.election_type }} is scheduled to end at
                                    {{ election.voting_ends_at | moment("MM/DD/YYYY HH:mm:ss") }} (UTC).
                                </div>
                                <div v-else>
                                    This {{ election.election_type }}
                                    ends at the administrator's discretion.
                                </div>
                                <br/>
                            </div>

                            <div v-if="election.is_private && voter">
                                <br/>
                                This election is <span>private</span>. You are signed in as eligible voter <span>
                                {{ voter.name }}</span>.
                            </div>

                            <br/>

                        </div>

                        <div v-else>
                            <span class="highlight-box round">voting is not yet open</span>
                            <br/><br/>
                        </div>

                        <div v-if="user">
                            <div v-if="voter">
                                <div style="padding-top:1px;">
                                    You are registered to vote in this {{ election.election_type }}.
                                    <div v-if="election.use_voter_aliases">Your voter alias is {{ voter.alias }}.</div>
                                </div>

                            </div>
                            <div v-else>
                                <div v-if="election.result">
                                </div>
                                <div v-else>
                                    <div v-if="election.openreg">
                                        <div v-if="eligible_p">
                                            You are eligible to vote in this election.
                                        </div>
                                        <div v-else>
                                            You are <span>not eligible</span> to vote in this {{
                                                election.election_type
                                            }}.
                                        </div>
                                    </div>
                                    <div v-else>
                                        You are <span>not eligible</span> to vote in this {{ election.election_type }}.
                                        <br/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div v-if="election.is_auth_user_admin && election.voting_ends_at && !election.tallying_started_at">
                    <br/>
                    <router-link :to="{ name: 'election@extend', params:{ slug: election.slug }}">extend voting
                    </router-link>
                    <br/>
                </div>

                <div
                    style="background: lightyellow; padding:5px; padding-left: 10px; margin-top: 15px; border: 1px solid #aaa; width: 720px;"
                    class="round">
                    <a href="javascript:void(0)" @click="show_audit_body=!show_audit_body">Audit Info</a>
                    <div v-show="show_audit_body">
                        <br/>Election URL:<br/>
                        <tt style="font-size: 1.2em;"><a :href="election.url">{{ election.url }}</a></tt>

                        <div v-if="election.frozen_at">
                            <br/>
                            <br/>Election Fingerprint:<br/>
                            <tt style="font-size: 1.3em; font-weight: bold;">{{ election.hash }}</tt>

                            <!--              <div v-if="votes">-->
                            <!--                <p>Your smart ballot tracker:<br/>-->
                            <!--                  <tt style="font-size:14pt; font-weight: bold;">{{ votes.0.vote_hash }}</tt>-->
                            <!--                </p>-->
                            <!--              </div>-->

                            <p style="font-size: 1.3em;">
                                <a href="election@voters@list-pretty election.slug">Ballot Tracking Center</a> &nbsp;|
                                &nbsp;
                                <a href="election@audited-ballots election.slug">Audited Ballots</a>
                            </p>

                        </div>

                        <div v-if="!election.voting_started_at">
                            <p style="font-size: 1.2em;">
                                <router-link :to="'/elections/' + election.slug + '/vote'">preview booth
                                </router-link>
                            </p>
                        </div>

                        <div v-if="election.voting_has_stopped">
                            <div style="font-size: 1.2em;">
                                <div v-if="election.result">
                                    verify
                                    <a target="_blank"
                                       href="/verifier/verify.html?election_url=election@view election.slug">election
                                        tally</a>.
                                </div>
                                review the <a href="vote_url">voting booth</a>.
                            </div>

                        </div>

                    </div>
                </div>

            </div>

            <br/>

        </div>

        <!-- TODO rest of helios/election_view.html -->

    </div>

</template>

<script>

import FreezeElectionModal from "../../components/FreezeElectionModal";
import {EventBus} from "../../event-bus";
import Election from "../../Models/Election";

export default {
    name: "Election",

    data() {
        return {
            show_badge_body: false,
            show_audit_body: false,
            //
            admin_p: null,
            can_feature_p: null,
            election: null,
            show_result: null,
            user: null,
            voter: null,
        }
    },

    watch: {
        $route(to, from) {
            this.fetch_election(to.params.slug);
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_election(slug);
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

        copy_election() {
            if (window.confirm('Are you sure you want to copy this election?')) {
                let self = this;
                this.election.copy_election()
                    .then(election => {
                        self.$toastr.success("OK");
                        self.$router.push({name: 'election@view', params: {slug: election.slug}})
                    })
                    .catch(e => {
                        self.$toastr.error("Error");
                    })
            }
        },

        set_featured(featured) {
            let self = this;
            this.election.set_featured(featured)
                .then(election => {
                    self.$toastr.success("OK");
                    self.election = election
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        set_archived(archived) {
            let self = this;
            this.election.set_archived(archived)
                .then(election => {
                    self.$toastr.success("OK");
                    self.election = election
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        freeze() {
            let self = this;

            EventBus.$on('frozenElection', function (election) {
                self.election = election;
            });

            this.$modal.show(FreezeElectionModal, {
                election: this.election
            }, {
                height: 'auto'
            });

        }

    }
}
</script>

<style scoped>

</style>
