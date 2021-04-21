<template>
    <div>
        <p v-if="errors" style="color: red;">
            {{ errors }}
        </p>

        <form>

            <div class="form-group row">
                <label for="name" class="col-sm-12 col-lg-2">Election name</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="text" class="form-control" v-model="post.name" id="name" size="100">
                    <small id="nameHelp" class="form-text text-muted">
                        The pretty name for your election, e.g. My Club 2010 Election.
                    </small>
                </div>
            </div>

            <div class="form-group row">
                <label for="slug" class="col-sm-12 col-lg-2">Slug</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="text" class="form-control" v-model="post.slug" id="slug" size="40">
                    <small id="slugHelp" class="form-text text-muted">
                        No spaces, will be part of the URL for your election, e.g. my-club-2010.
                    </small>
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-12 col-lg-2">Description</label>
                <div class="col-sm-12 col-lg-10">
                <textarea v-model="post.description" id="description" maxlength="4000" cols="70" wrap="soft"
                          class="form-control"></textarea>
                </div>
            </div>

            <!--            <select v-model="post.election_type">-->
            <!--                <option v-for="election_type in election_types" :value="election_type[0]">-->
            <!--                    {{ election_type[1] }}-->
            <!--                </option>-->
            <!--            </select>-->

            <div class="form-group row">
                <label for="cryptosystem" class="col-sm-12 col-lg-2">Cryptosystem</label>
                <div class="col-sm-12 col-lg-10">
                    <!-- TODO from API -->
                    <select v-model="post.cryptosystem" v-if="election == null" class="form-control" id="cryptosystem">
                        <option value="rsa">RSA</option>
                        <option value="eg">ElGamal</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="anonymization_method" class="col-sm-12 col-lg-2">Anonymization method</label>
                <div class="col-sm-12 col-lg-10">
                    <!-- TODO from API -->
                    <select v-model="post.anonymization_method" v-if="election == null" class="form-control"
                            id="anonymization_method">
                        <option value="enc_mixnet">Encryption Mixnet</option>
                        <option value="dec_mixnet">Decryption Mixnet</option>
                        <option value="homomorphic">Omomorphic encryption</option>
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="use_voter_aliases" class="col-sm-12 col-lg-2">Use voter Alias?</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="checkbox" v-model="post.use_voter_aliases" id="use_voter_aliases" class="form-control">
                    <small id="useVoterAliasesHelp" class="form-text text-muted">
                        If selected, voter identities will be replaced with aliases, e.g. "V12", in the ballot tracking
                        center
                    </small>
                </div>
            </div>

            <div class="form-group row">
                <label for="randomize_answer_order" class="col-sm-12 col-lg-2">Randomize answer order?</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="checkbox" v-model="post.randomize_answer_order" id="randomize_answer_order"
                           class="form-control">
                    <small id="randomizeAnswerOrderHelp" class="form-text text-muted">
                        Enable this if you want the answers to questions to appear in random order for each voter
                    </small>
                </div>
            </div>

            <div class="form-group row">
                <label for="is_private" class="col-sm-12 col-lg-2">Private?</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="checkbox" v-model="post.is_private" id="is_private" class="form-control">
                    <small class="form-text text-muted">A private election is only visible to registered voters.</small>
                </div>
            </div>

            <div class="form-group row">
                <label for="help_email" class="col-sm-12 col-lg-2">Help Email Address</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="email" v-model="post.help_email" id="help_email" class="form-control">
                    <small class="form-text text-muted">
                        An email address voters should contact if they need help.
                    </small>
                </div>
            </div>

            <div class="form-group row">
                <label for="info_url" class="col-sm-12 col-lg-2">Election Info Download URL</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="url" v-model="post.info_url" id="info_url" class="form-control">
                    <small class="form-text text-muted">
                        the URL of a PDF document that contains extra election information, e.g. candidate bios and
                        statements
                    </small>
                </div>
            </div>

            <div class="form-group row">
                <label for="voting_starts_at" class="col-sm-12 col-lg-2">Voting starts at</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="datetime-local" v-model="post.voting_starts_at" class="form-control"
                           id="voting_starts_at">
                    <small class="form-text text-muted">UTC date and time when voting begins</small>
                </div>
            </div>

            <div class="form-group row">
                <label for="voting_ends_at" class="col-sm-12 col-lg-2">Voting ends at</label>
                <div class="col-sm-12 col-lg-10">
                    <input type="datetime-local" v-model="post.voting_ends_at" class="form-control" id="voting_ends_at">
                    <small class="form-text text-muted">UTC date and time when voting ends</small>
                </div>
            </div>

        </form>

        <button class="btn btn-success" @click="submit">Next</button>

    </div>
</template>

<script>

import Election from "../Models/Election";

export default {
    name: "ElectionEditor",

    props: {
        election: {
            required: false,
            type: Election
        }
    },

    data() {
        return {
            post: new Election(),
            election_types: null,
            errors: null,
        }
    },

    mounted() {

        if (this.election != null) {
            this.post = Object.assign({}, this.election);
            Object.setPrototypeOf(this.post, Election.prototype);
        }

        let self = this;
        this.$http.get(BASE_URL + '/api/elections') // TODO change
            .then(response => {
                self.election_types = response.data.election_types;
                if (self.election == null) {
                    //if creating new
                    self.help_email = response.data.help_email;
                    self.is_private = response.data.is_private;
                }
            })
            .catch(e => {
                self.$toastr.error("Error");
            });
    },

    methods: {

        submit() {
            this.errors = null;
            if (this.election) {
                return this.update(this.election);
            } else {
                return this.store();
            }
        },

        store() { // TODO
            let self = this;
            let postData = Object.assign({}, self.post);
            // postData.voting_starts_at.replace("T", " ");
            // postData.voting_ends_at.replace("T", " ");
            this.$http.post(BASE_URL + '/api/elections', postData)
                .then(response => {
                    self.$toastr.success("OK");
                    self.$router.push({name: 'election@view', params: {slug: response.data.slug}});
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    if (e.response) {
                        // validation error
                        self.errors = e.response.data.errors
                    }
                    console.log(e)
                });
        },

        update(election) { // TODO
            let self = this;
            let postData = Object.assign({}, self.post);
            // postData.voting_starts_at.replace("T", " ");
            // postData.voting_ends_at.replace("T", " ");
            this.$http.put(BASE_URL + '/api/elections/' + election.slug, postData)
                .then(response => {
                    self.$toastr.success("OK");
                    self.$router.push({name: 'election@view', params: {slug: response.data.slug}});
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    if (e.response) {
                        // validation error
                        self.errors = e.response.data.errors
                    }
                    console.log(e)
                });
        }

    }
}
</script>

<style scoped>

</style>
