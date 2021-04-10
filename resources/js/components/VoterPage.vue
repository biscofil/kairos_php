<template>
    <div v-if="voters != null">

        <div v-if="election.num_voters > 20">
            <p v-if="query">
                <span>searching for <u>{{ query }}</u>.</span>
                <a href="javascript:void(0)" @click="clear_query_and_fetch" class="brackets_around">
                    clear search
                </a>
            </p>
            <b>search</b>:
            <input type="text" name="query" v-model="query"/>
            <button @click="fetch_voters">search</button>
        </div>

        <p>
            <b>
        <span v-if="election.num_cast_votes">
           {{ election.num_cast_votes }} cast vote{{ election.num_cast_votes > 1 ? "s" : "" }}
        </span>
                <span v-else>no votes yet</span>
            </b>
        </p>

        <div v-if="voters_page.has_previous">
            <a href="javascript:void(0)" @click="fetch_voters(voters_page.previous_page_number)">
                previous {{ limit }}
            </a>
            &nbsp;&nbsp;
        </div>

        <div v-if="voters_page.has_next">
            <a href="javascript:void(0)" @click="fetch_voters(voters_page.next_page_number)">
                next {{ limit }}
            </a> &nbsp;&nbsp;
        </div>

        Voters {{ voters_page.start_index }} - {{ voters_page.end_index }} (of {{ total_voters }})&nbsp;&nbsp;

        <table v-if="voters.length" class="pretty">
            <thead>
            <tr>
                <th v-if="election.is_auth_user_admin" style="width: 80px;">Actions</th>
                <th v-if="election.is_auth_user_admin">Login</th>
                <th v-if="election.is_auth_user_admin">Email Address</th>
                <th v-if="election.is_auth_user_admin || !election.use_voter_aliases">Name</th>
                <th v-if="election.use_voter_aliases">Alias</th>
                <th>Smart Ballot Tracker</th>
            </tr>
            </thead>

            <tbody>
            <tr v-for="voter in voters">
                <td v-if="election.is_auth_user_admin" style="white-space: nowrap;">
                    [<a v-if="election.frozen_at"
                        href="election@voters@email election.slug %}?voter_id={ { voter.voter_login_id } }">email</a>]
                    [<a onclick="return confirm('are you sure you want to remove { {voter.name} } ?');"
                        href="election@voter@delete election.slug voter.slug ">x</a>]
                </td>
                <td v-if="election.is_auth_user_admin">{{ voter.voter_login_id }}</td>
                <td v-if="election.is_auth_user_admin">{{ voter.voter_email }}</td>
                <td v-if="election.is_auth_user_admin || !election.use_voter_aliases">
                    <img class="small-logo" src="/static/auth/login-icons/voter.voter_type.png"
                         :alt="voter.voter_type"/>
                    {{ voter.name }}
                </td>
                <td v-if="election.use_voter_aliases ">{{ voter.alias }}</td>
                <td>
                    <tt style="font-size: 1.4em;">
              <span v-if="voter.vote_hash">
                {{ voter.vote_hash }}
                <span style="font-size:0.8em;">
                  [<a href="shortcut@vote vote_tinyhash=voter.vote_tinyhash ">view</a>]
                </span>
              </span>
                        <span v-else>&mdash;</span>
                    </tt></td>
            </tr>
            </tbody>
        </table>

        <div v-else>
            <br/><br/>
            <span>no voters.</span>
        </div>

    </div>
</template>

<script>

export default {
    name: "VoterPage",

    props: {
        election: {
            required: true,
            type: Object
        },
    },

    data() {
        return {
            voters_page: null,
            query: "",
            limit: 50,
            voters: null
        }
    },

    mounted() {
        this.fetch_voters();
    },

    methods: {

        clear_query_and_fetch() {
            this.query = "";
            this.fetch_voters();
        },

        fetch_voters(page = 1) {// TODO check first page
            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + this.election.slug + '/voters/list?page=' + page + "&limit=" + this.limit + "&q=" + this.query)
                .then(response => {
                    self.voters_page = response.data.voters_page
                    self.voters = response.data.voters
                    self.election.is_auth_user_admin = response.data.election.is_auth_user_admin
                    self.email_voters = response.data.email_voters
                    self.limit = response.data.limit
                    self.total_voters = response.data.total_voters
                    self.upload_p = response.data.upload_p
                    self.query = response.data.q
                    self.voter_files = response.data.voter_files
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        }

    }

}
</script>

<style scoped>

</style>
