<template>

    <div v-if="election">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Elections</a></li>
                <li class="breadcrumb-item">
                    <router-link :to="{name:'election@view', params:{ slug: election.slug }}">{{
                            election.name
                        }}
                    </router-link>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Trustees</li>
            </ol>
        </nav>

        <h3 class="title">
            {{ election.name }} &mdash; Trustees
            <!--            <router-link :to="{name:'election@view', params:{ slug: election.slug }}" class="brackets_around">-->
            <!--                back to election-->
            <!--            </router-link>-->
        </h3>

        <p>
            Trustees are responsible for decrypting the election result.<br/>
            Each trustee generates a keypair and submits the public portion to Helios.<br/>
            When it's time to decrypt, each trustee needs to provide their secret key.
        </p>

        <div v-if="!election.frozen_at">
            <p>
                Helios is automatically your first trustee and will handle its keypair generation and decryption
                automatically.<br/>
                You may add additional trustees if you want, and you can even remove the Helios trustee.<br/>
                However, we recommend you do this only if you have a solid understanding of the trustee's role.
            </p>

            <div v-if="election.is_auth_user_admin">
                <a @click="add_trustee" href="javascript:void(0)" class="btn btn-sm btn-success">New trustee</a>
            </div>
        </div>

        <div class="list-group list-group-flush" v-if="election.trustees && election.trustees.length">
            <li v-for="(trustee,idx) in election.trustees" class="list-group-item">
                <h5>
                    <country-flag v-if="trustee.peer_server && trustee.peer_server.country_code"
                                  :country='trustee.peer_server.country_code'/>
                    Trustee #{{ idx + 1 }}:
                    <div v-if="election.is_auth_user_admin">
                        <!-- TODO only shown for admin -->
                        <div v-if="trustee.user">
                            <!-- Use trustee -->
                            ({{ trustee.user.email }})
                            <a v-if="!election.frozen_at" @click="remove_trustee(trustee)"
                               href="javascript:void(0)" class="brackets_around">x</a>
                        </div>
                        <div v-else-if="trustee.peer_server">
                            <!-- Use trustee -->
                            ({{ trustee.peer_server.name }})
                            <a v-if="!election.frozen_at" @click="remove_trustee(trustee)"
                               href="javascript:void(0)" class="brackets_around">x</a>
                        </div>
                    </div>
                </h5>

                <p>
                    <span v-if="trustee.public_key_hash">
                        Public Key Fingerprint:
                        <small>{{ trustee.public_key_hash }}</small>
                    </span>
                    <span v-else>
                        <span v-if="trustee.user" class="badge badge-warning">No public key uploaded yet.</span>
                        <span v-else-if="trustee.peer_server" class="badge badge-info">The public key will be sent after the election freeze.</span>
                    </span>
                </p>

                <div v-if="election.encrypted_tally">
                    <b v-if="trustee.decryption_factors">tally recorded for this trustee.</b>
                    <span v-else>waiting for this trustee's tally</span>
                </div>

            </li>
        </ul>

    </div>

</template>

<script>
import NewTrusteeModal from "../../components/NewTrusteeModal";
import {EventBus} from "../../event-bus";
import Election from "../../Models/Election";
import Trustee from "../../Models/Trustee";
import CountryFlag from 'vue-country-flag';

export default {
    name: "ElectionTrustees",

    components: {
        CountryFlag
    },

    data() {
        return {
            election: null,
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_election(slug);
    },

    watch: {
        $route(to, from) {
            this.fetch_election(to.params.slug);
        }
    },

    methods: {
        fetch_election(slug) {
            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + slug + '/trustees')
                .then(response => {
                    self.election = Election.fromJSONObject(response.data);
                    document.title = "Trustees for " + self.election.name;
                })
                .catch(e => {
                    console.log(e);
                    self.$toastr.error("Error");
                });
        },

        /**
         *
         * @param trustee : Trustee
         */
        remove_trustee(trustee) {
            let msg = 'Are you sure you want to remove this trustee?';
            // if (trustee.secret_key) {
            //     msg = 'Are you sure you want to remove Helios as a trustee?';
            // }

            if (window.confirm(msg)) {
                let self = this;
                this.$http.delete(BASE_URL + '/api/elections/' + this.election.slug + '/trustees/' + trustee.uuid)
                    .then(response => {
                        self.election.trustees = response.data.election.trustees.map(trustee => {
                            return Trustee.fromJSONObject(trustee);
                        });
                        self.$toastr.success("Done");
                    })
                    .catch(e => {
                        console.log(e);
                        self.$toastr.error("Error");
                    });
            }
        },

        add_trustee() {

            let self = this;
            EventBus.$on('addedTrustee', function (election) {
                self.election.trustees = election.trustees.map(trustee => {
                    return Trustee.fromJSONObject(trustee);
                });
            });

            this.$modal.show(NewTrusteeModal, {
                election: this.election
            }, {
                height: 'auto'
            });

        },

    }

}
</script>
