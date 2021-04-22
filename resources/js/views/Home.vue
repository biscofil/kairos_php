<template>
    <div class="row" v-if="ready">

        <div class="col-sm-12">
            <a href="/">
                <img border="0" :src="$root.settings.MAIN_LOGO_URL"/>
            </a>
        </div>

        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h5>Featured elections</h5>
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
                    <span v-else>no featured elections at the moment</span>
                </div>
            </div>
        </div>

        <div class="col-sm-12" v-if="$store.getters.isLogged">
            <div class="card">
                <div class="card-body">
                    <h5>Administration</h5>
                    <ul v-if="elections_administered">
                        <li v-for="election in elections_administered">
                            <router-link :to="{name: 'election@view', params:  { slug: election.slug }}">
                                {{ election.name }}
                            </router-link>
                        </li>
                    </ul>
                    <span v-else>none yet</span>
                    <router-link :to="{name: 'elections@administered'}" class="btn btn-info btn-sm">
                        see all
                    </router-link>
                </div>
            </div>
        </div>

        <div class="col-sm-12" v-if="$store.getters.isLogged">
            <div class="card">
                <div class="card-body">
                    <h5>Recent Votes</h5>
                    <ul v-if="elections_voted">
                        <li v-for="election in elections_voted">
                            <router-link :to="{name: 'election@view', params:  { slug :election.slug }}">
                                {{ election.name }}
                            </router-link>
                        </li>
                    </ul>
                    <span v-else>none yet</span>
                    <router-link :to="{name: 'elections@voted'}" class="btn btn-info btn-sm">see all</router-link>
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
.card {
    margin: 10px;
}
</style>
