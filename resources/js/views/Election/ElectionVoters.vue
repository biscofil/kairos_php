<template>
    <div v-if="election">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Elections</a></li>
                <li class="breadcrumb-item">
                    <router-link :to="{name:'election@view', params:{ slug: election.slug }}" >{{ election.name }}</router-link>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Voters and Ballot Tracking Center</li>
            </ol>
        </nav>

        <h3 class="title">
            {{ election.name }} &mdash; Voters and Ballot Tracking Center
            <router-link :to="{name:'election@view', params:{ slug: election.slug }}" class="brackets_around">
                back to election
            </router-link>
        </h3>

        <p>
            <b>Who can vote?</b>
            Only the voters listed here
        </p>

        <div v-if="election.is_auth_user_admin && !election.frozen_at">
            <div v-if="election.is_private">
                <span>Your election is marked private, which means you cannot open registration up more widely</span>.<br/>
            </div>
        </div>

        <p v-if="email_voters && election.frozen_at && election.is_auth_user_admin">
            <a class="button" href="election@voters@email election.slug">email voters</a>
        </p>

        <div v-if="election.is_auth_user_admin && upload_p">
            <BulkUploader/>
        </div>

        <VoterPage :election="election"/>

    </div>
</template>

<script>
import BulkUploader from "../../components/BulkUploader";
import VoterPage from "../../components/VoterPage";

export default {

    name: "ElectionVoters",

    components: {VoterPage, BulkUploader},

    data() {
        return {
            election: null,
            email_voters: null,
            upload_p: null,
            voter_files: null,
            categories: null,
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

        set_data_from_response(data) {
            this.election = data.election;
            this.email_voters = data.email_voters;
            this.upload_p = data.upload_p;
            this.voter_files = data.voter_files;
            this.categories = data.categories;
        },

        fetch_election(slug) {
            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + slug)
                .then(response => {
                    self.election = response.data;
                    // self.set_data_from_response(response.data);
                    document.title = "Voters &amp; Ballot Tracking Center for " + self.election.name;
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

    }
}
</script>

<style scoped>

</style>
