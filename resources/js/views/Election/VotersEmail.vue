<template>
    <div v-if="election">

        <h2 class="title">{{ election.name }} &mdash; Contact Voters
            <router-link class="brackets_around" :to="{ name:'election@view', params:{slug:election.slug} }">
                back to election
            </router-link>
        </h2>

        <p v-if="voter">
            You are sending this email to a specific user: <b>{{ voter.name }} ({{ voter.voter_id }})</b>
        </p>

        <div>
            <b>Templates</b>:

            <div v-for="template_option in templates">
                <b v-if="template_option[0] == template">{{ template_option[1] }}</b>
                <a v-else href="?template={ { template_option.0 } }&voter_id={ { voter.voter_login_id } }">
                    {{ template_option[1] }}
                </a>
                &nbsp;&nbsp;&nbsp;
            </div>
        </div>

        <pre style="margin:10px; border: 1px solid #888; padding:20px">
      Subject: {{ default_subject }}

      {{ default_body }}

      </pre>

        <p>
            You may tweak the subject and add a custom message using the form below.
        </p>

        <div>

            <input type="text" name="voter_id" v-model="subject" max_length="80"/>
            <input type="text" name="voter_id" v-model="body" max_length="4000"/>

            <select v-model="send_to">
                <option value="all" selected>all voters</option>
                <option value="voted">voters who have cast a ballot</option>
                <option value="not-voted">voters who have not yet cast a ballot</option>
            </select>

            <label for="">&nbsp;</label>
            <input type="submit" value="Send" id="send_button" class="button"/>
        </div>

        <div id="processing">
        </div>

        <div id="done" style="display:none;">
            Done, go <a href="{% url  election@view election.slug %}">back to election</a>.
        </div>

        <div id="error" style="display:none;">
            Error emailing participants. Check server settings, make sure there's an SMTP server.
        </div>

    </div>
</template>

<script>

export default {
    name: "VotersEmail",

    data() {
        return {
            election: null,
            voter_id: null,
            //
            send_to: 'all',
            subject: '',
            body: '',
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
            this.$http.get(BASE_URL + '/api/elections/' + slug + '/voters/email')
                .then(response => {
                    self.election = response.data.election;
                    document.title = "Contact Voters for " + self.election.name;
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
