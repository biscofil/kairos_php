<template>
    <div v-if="election && peer_servers" class="card">
        <div class="card-header">
            New Trustee
            <a href="javascr ipt:void(0)" @click="closeModal" class="brackets_around">cancel</a>
        </div>
        <div class="card-body">
            <p>
                Adding your own trustee requires a good bit more work to tally the election.
                <br><br>
                You will need to have trustees generate keypairs and safeguard their secret key.
                <br><br>
                If you are not sure what that means, we strongly recommend clicking Cancel and letting Helios tally the
                election for you.
            </p>

            <div class="form">

                <div class="form-group row">
                    <label class="col-sm-12 col-lg-2" for="email"> Type </label>
                    <div class="col-sm-12 col-lg-10">
                        <label>
                            <input type="radio" name="trustee_type" v-model="trustee_type" value="user"/>
                            User
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="trustee_type" v-model="trustee_type" value="server"/>
                            Server
                        </label>
                    </div>
                </div>

                <div class="form-group row" v-if="trustee_type === 'user'">
                    <label class="col-sm-12 col-lg-2" for="email">Email</label>
                    <div class="col-sm-12 col-lg-10">
                        <input type="email" class="form-control" id="email" size="60" v-model="email"/>
                    </div>
                </div>

                <div class="form-group row" v-if="trustee_type === 'server'">
                    <label class="col-sm-12 col-lg-2" for="url">Server</label>
                    <div class="col-sm-12 col-lg-10">
                        <select class="form-control" v-model="peer_server_id">
                            <option v-for="peer_server in peer_servers" :value="peer_server.id">
                                {{ peer_server.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <button class="btn btn-success" @click="submit">Add Trustee</button>

            </div>
        </div>
    </div>
</template>

<script>

import {EventBus} from "../event-bus";

export default {
    name: "NewTrusteeModal",

    props: {
        election: {
            required: true,
            type: Object
        },
    },

    data() {
        return {
            peer_servers: null,
            trustee_type: "user",
            email: "",
            peer_server_id: "",
        }
    },

    mounted() {
        let self = this;
        this.$http.get(BASE_URL + '/api/p2p')
            .then(response => {
                self.peer_servers = response.data;
            })
            .catch(e => {
                self.$toastr.error("Error");
            });
    },

    methods: {
        submit() {
            let self = this;
            this.$http.post(BASE_URL + '/api/elections/' + this.election.slug + '/trustees', {
                type: this.trustee_type,
                peer_server_id: this.peer_server_id,
                email: this.email,
            })
                .then(response => {
                    EventBus.$emit('addedTrustee', response.data.election);
                    self.$toastr.success("Done");
                    self.closeModal();
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        closeModal() {
            this.$emit('close');
        }
    }
}
</script>

<style scoped>

</style>
