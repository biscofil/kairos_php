import Vue from 'vue';
import Vuex from 'vuex';
import axios from "axios";

Vue.use(Vuex);

if (localStorage.getItem('auth_user')) {
    let user = JSON.parse(localStorage.getItem('auth_user'));
    axios.defaults.headers.common['Authorization'] = `Bearer ${user.access_token}`;
}

export const store = new Vuex.Store({

    state: {
        user: localStorage.getItem('auth_user')
            ? JSON.parse(localStorage.getItem('auth_user'))
            : null,
    },

    actions: {
        login({commit}, user) {
            commit('login', user);
        },
        logout({commit}) {
            commit('logout');
        },
    },

    mutations: {
        setAuthUser(state, user) {
            if (user) {
                this.commit('login', user);
            } else {
                this.commit('logout');
            }
        },
        login(state, user) {
            console.log("login");
            if (user.access_token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${user.access_token}`;
            }
            localStorage.setItem('auth_user', JSON.stringify(user));
            state.user = user;
        },
        logout(state) {
            console.log("logout");
            localStorage.removeItem('auth_user');
            state.user = null;
        },
    },
    getters: {
        isLogged: (state) => {
            return state.user !== null;
        },
        user: (state) => {
            return state.user;
        }
    }
});

function clearSession() {
    console.log("clearSession");
    store.dispatch('logout');
}

function updateSession(headers) {
    console.log("updateSession");
    let user = store.getters.user;
    console.log(user);
    if (user) {
        console.log("############### headers ###############");
        console.log(headers.access_token);
        console.log(headers.access_token_expires_in);
        console.log("#######################################");
        if (headers.access_token && headers.access_token_expires_in) {
            console.log("we have headers -> update auth");
            user['access_token'] = headers.access_token;
            user['access_token_expires_in'] = headers.access_token_expires_in;
            store.dispatch('login', user);
        } else {
            console.log("we have no headers -> clearSession");
            clearSession();
        }
    }
}

axios.interceptors.response.use(function (response) {
    // Any status code that lie within the range of 2xx cause this function to trigger
    // Do something with response data
    // TODO updateSession(response.headers);
    return response;
}, function (error) {
    // Any status codes that falls outside the range of 2xx cause this function to trigger
    // Do something with response error
    // TODO clearSession();
    return Promise.reject(error);
});
