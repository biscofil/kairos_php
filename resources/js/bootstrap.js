window._ = require('lodash');

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/*
export default axios.create({
    baseURL: "http://localhost/",
    headers: {
        "Accept": "application/json",
        "Content-Type": "application/json"
    }
})
 */
