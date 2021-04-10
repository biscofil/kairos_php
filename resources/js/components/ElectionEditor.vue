<template>
    <div>
        <p v-if="errors" style="color: red;">
            {{ errors }}
        </p>

        <form>

            <input type="text" v-model="post.name" size="100">
            <p>the pretty name for your election, e.g. My Club 2010 Election</p>

            <input type="text" v-model="post.slug" size="40">
            <p>no spaces, will be part of the URL for your election, e.g. my-club-2010</p>

            <textarea v-model="post.description" maxlength="4000" cols="70" wrap="soft"></textarea>

<!--            <select v-model="post.election_type">-->
<!--                <option v-for="election_type in election_types" :value="election_type[0]">-->
<!--                    {{ election_type[1] }}-->
<!--                </option>-->
<!--            </select>-->

            <!-- TODO from API -->
            <select v-model="post.cryptosystem" v-if="election == null">
                <option value="rsa">RSA</option>
                <option value="eg">ElGamal</option>
            </select>

            <label for="use_voter_aliases">Use voter Alias?</label>
            <input type="checkbox" v-model="post.use_voter_aliases" id="use_voter_aliases">
            <p>If selected, voter identities will be replaced with aliases, e.g. "V12", in the ballot tracking
                center</p>

            <label for="randomize_answer_order">Randomize answer order?</label>
            <input type="checkbox" v-model="post.randomize_answer_order" id="randomize_answer_order">
            <p>Enable this if you want the answers to questions to appear in random order for each voter</p>

            <label for="is_private">Privater?</label>
            <input type="checkbox" v-model="post.is_private" id="is_private">
            <p>A private election is only visible to registered voters.</p>

            <label for="help_email">Help Email Address</label>
            <input type="email" v-model="post.help_email" id="help_email">
            <p>An email address voters should contact if they need help.</p>

            <label for="info_url">Election Info Download URL</label>
            <input type="url" v-model="post.info_url" id="info_url">
            <p>
                the URL of a PDF document that contains extra election information, e.g. candidate bios and statements
            </p>

            <input type="datetime-local" v-model="post.voting_starts_at">
            <p>UTC date and time when voting begins</p>

            <input type="datetime-local" v-model="post.voting_ends_at">
            <p>UTC date and time when voting ends</p>


        </form>

        <button @click="submit">Next</button>

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
