<template>
    <div>

        <p v-if="default_auth_system">
            <!--      <a class="small button" href="{% url "auth@start" system_name=default_auth_system %}?return_url={{return_url}}">-->
            <a class="small button" href="auth@start">
                Log In
            </a>
        </p>

        <div v-else>
            <div v-for="auth_system in enabled_auth_systems">
                <p v-if="auth_system.name !== 'password'">
                    <a @click="AuthProvider(auth_system.name)" href="javascript:void(0)" style="font-size: 1.4em;">
                        <img :src="'/assets/img/login-icons/' + auth_system.name + '.png'"
                             :alt="auth_system.name"/>
                        {{ auth_system.name }}
                    </a>
                </p>
            </div>
        </div>
    </div>

</template>

<script>

import Vue from 'vue'

import VueSocialauth from 'vue-social-auth'

Vue.use(VueSocialauth, {
    providers: {}
})

export default {
    name: "LoginBox",

    props: {
        default_auth_system: {
            required: true
        },
        enabled_auth_systems: {
            type: Array,
            required: true
        }
    },

    mounted() {
        let self = this;
        // has to match https://console.cloud.google.com/apis/credentials?project=
        this.enabled_auth_systems.forEach(enabled_auth_system => {
            self.$auth.options.providers[enabled_auth_system.name].clientId = enabled_auth_system.clientId;
            self.$auth.options.providers[enabled_auth_system.name].redirectUri = BASE_URL + '/api/auth/after/' + enabled_auth_system.name;
        });
    },

    methods: {
        AuthProvider(provider) {
            let self = this;
            this.$auth.authenticate(provider)
                .then(response => {
                    self.SocialLogin(provider, response)
                })
                .catch(err => {
                    console.log({err: err})
                });
        },

        SocialLogin(provider, response) {
            let self = this;
            this.$http.post(BASE_URL + '/api/auth/after/' + provider, {
                'code': response.code
            })
                .then(response => {
                    let user = response.data.user;
                    user['access_token'] = response.data.access_token;
                    self.$store.dispatch('login', user);
                })
                .catch(err => {
                    console.log({err: err})
                });
        },
    }
}
</script>

<style scoped>
img {
    height: 35px;
    border: 0px;
}
</style>
