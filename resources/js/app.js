import axios from "axios";
import Vue from 'vue';
import VueRouter from 'vue-router';
import {store} from './store';
import router from './router.js';

import VueToastr2 from 'vue-toastr-2/dist/vue-toastr-2';
import Toastr from 'toastr';
import VModal from 'vue-js-modal/dist';
import VueAxios from 'vue-axios';

import vue_moment from "vue-moment";

require('./bootstrap');

window.Vue = Vue;
window.toastr = Toastr;
Vue.prototype.$http = axios;
axios.defaults.timeout = 10000;

Vue.use(VueToastr2);
Vue.use(VModal, {dynamic: true, injectModalsContainer: true});
Vue.use(VueRouter);
Vue.use(VueAxios, axios);
Vue.use(vue_moment);

window.BASE_URL = 'http://localhost';

const app = new Vue({
    el: '#app',

    router,
    store,

    data: {
        //user: null,
        login_box: null,
        /*voter: {
            alias: false,
            name: "NAME",
            election: {
                name: "ELECTIONN"
            }
        },*/
        settings: null
    },

    created() {
        let self = this;
        this.$http.get('http://localhost/api/settings_auth')
            .then(response => {
                //settings
                self.settings = response.data.settings;
                // auth
                let user = response.data.user;
                if (user) {
                    // refreshed auth token
                    user['access_token'] = response.headers.access_token;
                    user['access_token_expires_in'] = response.headers.access_token_expires_in;
                    self.$store.dispatch('login', user);
                }
                self.login_box = response.data.login_box;
            })
            .catch(e => {
                console.log(e);
            });
    },

});
