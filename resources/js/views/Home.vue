<template>
    <div v-if="ready">

        <div class="row">
            <div class="large-5 columns large-centered">
                <a href="/">
                    <img border="0" :src="$root.settings.MAIN_LOGO_URL"/>
                </a>
            </div>
        </div>

        <div class="row">&nbsp;</div>

        <div class="large-9 columns">
            <div>

                <div>

                    <p>Helios offers <b>verifiable online elections</b>.</p>

                    <p>We believe democracy is important, whether it’s your book club, parent-teacher association,
                        student
                        government,
                        workers’ union, or state. So we’ve made truly verifiable elections as easy as everything else on
                        the
                        Web.</p>

                    <div>
                        Helios elections are:
                        <ul class="disc">
                            <li><b>private</b>: no one knows how you voted.</li>
                            <li><b>verifiable</b>: each voter gets a tracking number.</li>
                            <li><b>proven</b>: Helios is open-source, vetted by top-tier experts, and in use by major
                                organizations.
                            </li>
                        </ul>
                    </div>

                    <p>
                        More than <b>2,000,000 votes</b> have been cast using Helios.
                    </p>

                    <router-link v-if="$store.getters.isLogged && $store.getters.user.can_create_election"
                                 class="button" :to="{name: 'elections@new'}">
                        create an election
                    </router-link>

                </div>

                <div>

                    <div v-if="elections.length">
                        <div v-for="election in elections"> <!-- paragraph -->
                            <div class="panel">
                                <router-link style="font-size: 1.4em;"
                                             :to="{ name: 'election@view', params: {slug: election.slug}}">
                                    {{ election.name }}
                                </router-link>
                                <div v-if="$root.settings.SHOW_USER_INFO">
                                    <br> by {{ election.admin_name }}
                                </div>
                            </div>
                            <br>
                        </div>
                    </div>
                    <h4 v-else>no featured elections at the moment</h4>

                </div>

            </div>
        </div>

        <div class="large-3 columns" id="mystuff">
            <div class="row">&nbsp;</div>
            <div class="panel row">
                <div v-if="$store.getters.isLogged">
                    <!-- TODO <div class="row right">{{user.display_html_big|safe}}</div>-->
                    <div v-if="$store.getters.user.can_create_election">

                        <router-link class="small button" :to="{name: 'elections@new'}">
                            create election
                        </router-link>

                        <h5 class="subheader">Administration</h5>
                        <ul v-if="elections_administered">
                            <li v-for="election in elections_administered">
                                <router-link :to="{name: 'election@view', params:  { slug: election.slug }}">
                                    {{ election.name }}
                                </router-link>
                            </li>
                        </ul>
                        <span v-else>none yet</span>

                        <div class="row right">
                            <router-link :to="{name: 'elections@administered'}" class="tiny button">
                                see all
                            </router-link>
                        </div>
                        <div class="row"></div>
                    </div>

                    <h5 class="subheader">Recent Votes</h5>
                    <ul v-if="elections_voted">
                        <li v-for="election in elections_voted">
                            <router-link :to="{name: 'election@view', params:  { slug :election.slug }}">
                                {{ election.name }}
                            </router-link>
                        </li>
                    </ul>
                    <span v-else>none yet</span>

                    <div class="row right">
                        <router-link :to="{name: 'elections@voted'}" class="tiny button">see all</router-link>
                    </div>
                    <div class="row"></div>

                </div>

                <div v-else>

                    <div v-if="$root.settings.SHOW_LOGIN_OPTIONS && $root.login_box">
                        <h5>Log In to Start Voting</h5>
                        <LoginBox :default_auth_system="$root.login_box.default_auth_system"
                                  :enabled_auth_systems="$root.login_box.enabled_auth_systems"/>
                    </div>
                    <p v-else>
                        Select an election to start voting. You will be asked for your voting credentials after you
                        complete a
                        ballot.
                    </p>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import LoginBox from "../components/LoginBox";

export default {
    name: "Home",

    components: {LoginBox},

    data() {
        return {
            ready: false,
            //
            elections: null,
            elections_administered: null,
            elections_voted: null,
        }
    },

    mounted() {
        let self = this;
        this.$http.get(BASE_URL + '/api')
            .then(response => {
                //TODO self.settings = response.data.settings;
                self.elections = response.data.elections;
                self.elections_administered = response.data.elections_administered;
                self.elections_voted = response.data.elections_voted;
                self.ready = true;
            })
            .catch(e => {
                console.log(e);
            })
    }
}
</script>

<style scoped>

#mystuff {
    float: right;
    /*width: 250px;*/
    border-left: 1px solid #888;
    padding-left: 20px;
}
</style>
