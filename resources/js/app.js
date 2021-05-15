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
import LoginBox from "./components/LoginBox";

import 'vue-toastr-2/dist/vue-toastr-2.min.css';
import VuejsDialog from 'vuejs-dialog';
import 'vuejs-dialog/dist/vuejs-dialog.min.css'; // include the default style
require('./bootstrap');


Vue.use(VuejsDialog); // Tell Vue to install the plugin.


window.Vue = Vue;
window.toastr = Toastr;
Vue.prototype.$http = axios;
axios.defaults.timeout = 10000;

Vue.use(VueToastr2);
Vue.use(VModal, {dynamic: true, injectModalsContainer: true});
Vue.use(VueRouter);
Vue.use(VueAxios, axios);
Vue.use(vue_moment);

window.BASE_URL = window.location.protocol + "//" + window.location.host; // or take it from .env with process.env.MIX_APP_URL

const app = new Vue({
    el: '#app',

    router,
    store,

    components: {
        'loginbox': LoginBox
    },

    data: {
        login_box: null,
        settings: null,
        main_class : "container"
    },

    created() {
        let self = this;
        this.$http.get(BASE_URL + '/api/settings_auth')
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
