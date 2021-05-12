<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">

                <label>
                    Dominio
                    <input type="text" v-model="new_peer_domain">
                </label>
                <button class="btn btn-info" @click="addPeer">Aggiungi peer</button>

                <hr>

                <GlobusMap :servers="servers" :nodes="nodes" :links="links"/>

            </div>
            <div class="col-sm-12">
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

export default {
    name: "Network",

    components: {
        GlobusMap,
    },

    data() {
        return {
            servers: [],
            nodes: [],
            links: [],
            messages: [],
            new_peer_domain: null
        }
    },


    mounted() {

        const pusher = new Pusher('ddf35c236814ea416d00', {
            cluster: 'eu'
        });

        let self = this;
        axios.get(BASE_URL + "/api/p2p")
            .then(response => {
                self.servers = response.data;
                self.links = response.data.map(server => {
                    return {sid: "_me", tid: server.id};
                }).filter(server => {
                    return server.tid !== "_me";
                });
                self.nodes = response.data.map(server => {
                    return server;
                });
            })
            .catch(e => {
                console.log(e);
                self.$toastr.error("Error");
            });

        let channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function (data) {
            self.messages.push(JSON.stringify(data));
            self.onMessageReceived(data);
        });

    },

    methods: {
        onMessageReceived(message) {
        },

        addPeer() {
            let self = this;
            axios.post(BASE_URL + "/api/p2p/new_peer", {
                'domain': self.new_peer_domain
            })
                .then(response => {
                    console.log(response.data);
                    self.$toastr.success("Ok");
                })
                .catch(e => {
                    console.log(e);
                    self.$toastr.error("Error");
                });
        }
    }
}
</script>

<style scoped>

</style>
