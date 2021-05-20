<template>
    <div class="container-fluid">
        <div class="row" v-if="nodes">
            <div class="col-sm-6">

                <label>
                    Dominio
                    <input type="text" v-model="new_peer_domain">
                </label>
                <button class="btn btn-info" @click="addPeer">Aggiungi peer</button>

                <hr>

                <ul class="list-unstyled">
                    <li v-for="server in servers">
                        <country-flag v-if="server.country_code" :country='server.country_code'/>
                        <a href="javascript:void(0)" @click="flyTo(server)">{{ server.name }}</a>
                    </li>
                </ul>

                <hr>

                <WorldMap2 ref="map" :servers="servers" :nodes="nodes" :links="links"/>

            </div>
            <div class="col-sm-6">
                <h1>Log</h1>
                <ul>
                    <li v-for="message in messages">
                        <b>{{ message.messageSenderServer }}</b> {{ message.message }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import Pusher from 'pusher-js';
import WorldMap2 from "../../components/WorldMap2";
import CountryFlag from 'vue-country-flag';

// Enable pusher logging - don't include this in production
Pusher.logToConsole = false;

export default {
    name: "Network",

    components: {
        WorldMap2,
        CountryFlag
    },

    data() {
        return {
            servers: [],
            nodes: null,
            links: [],
            messages: [],
            new_peer_domain: null
        }
    },

    beforeDestroy() {
        // restore half width
        this.$root.main_class = "container";
    },

    mounted() {

        // set full width
        this.$root.main_class = "container-fluid";

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
                console.log("Network done");
            })
            .catch(e => {
                console.log(e);
                self.$toastr.error("Error");
            });

        let channel = pusher.subscribe('my-channel');
        channel.bind('my-event', function (data) {
            self.onMessageReceived(data);
        });

    },

    methods: {
        onMessageReceived(message) {
            this.messages.unshift(message);
            if (message.messageSenderServer && message.messageDestionationServer) {
                let from_domain = message.messageSenderServer;
                let to_domain = message.messageDestionationServer;
                this.$refs.map.flyPlane(from_domain, to_domain);
            }
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
        },

        flyTo(server) {
            if (!server.gps) {
                return;
            }
            this.$refs.map.flyTo(server);
        }
    }
}
</script>

<style scoped>
.list-unstyled > li > span, .list-unstyled > li > a {
    vertical-align: middle
}
</style>
