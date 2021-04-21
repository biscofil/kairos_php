<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12 col-lg-6">
                <GlobusMap :nodes="nodes" :links="links"/>
            </div>
            <div class="col-sm-12 col-lg-6">
                <h1>Log</h1>
                <ul>
                    <li v-for="message in messages">
                        {{ message }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import Pusher from 'pusher-js';
import GlobusMap from "../../components/GlobusMap";

// Enable pusher logging - don't include this in production
Pusher.logToConsole = true;

const pusher = new Pusher('ddf35c236814ea416d00', {
    cluster: 'eu'
});

export default {
    name: "Network",

    components: {
        GlobusMap,
    },

    data() {
        return {
            nodes: [],
            links: [],
            messages: []
        }
    },


    mounted() {
        let self = this;
        axios.get(BASE_URL + "/api/p2p")
            .then(response => {
                self.links = response.data.map(server => {
                    return {sid: "_me", tid: server.id};
                }).filter(server => {
                    return server.tid !== "_me";
                });
                self.nodes = response.data.map(server => {
                    return server;
                });
            });

        let channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function (data) {
            self.messages.push(JSON.stringify(data));
            self.onMessageReceived(data);
        });

    },

    methods: {
        onMessageReceived(message) {
        }
    }
}
</script>

<style scoped>

</style>
