<template>
    <span>
        <a v-for="auth_system in enabled_auth_systems"
           @click="authenticateWith(auth_system.name)"
           href="javascript:void(0)"
           style="font-size: 1.4em;">
            <img :src="'/vuesocial/' + auth_system.name + '_' + color + '.svg'" :alt="auth_system.name"/>
        </a>
    </span>

</template>

<script>

import Vue from 'vue'

import VueSocialauth from 'vue-social-auth'

Vue.use(VueSocialauth, {
    providers: {}
});

export default {
    name: "LoginBox",

    props: {
        default_auth_system: {
            required: true
        },
        enabled_auth_systems: {
            type: Array,
            required: true
        },
        color: {
            type: String,
            default: "color"
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
        authenticateWith(provider) {
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
    padding: 3px;
    height: 30px;
    border: 0px;
}
</style>
