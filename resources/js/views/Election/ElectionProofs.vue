<template>
    <div>
        <div v-if="election == null || proofs == null">
            Loading...
        </div>
        <div v-else>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Elections</a></li>
                    <li class="breadcrumb-item">
                        <router-link :to="{name:'election@view', params:{ slug: election.slug }}">{{
                                election.name
                            }}
                        </router-link>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Proofs</li>
                </ol>
            </nav>

            <h3 class="title">{{ election.name }} &mdash; Proofs</h3>

            <!-- TODO  v-if="election.anonymization_method === 'mixnet'" -->
            <MixNetProofs :proofs="proofs"></MixNetProofs>

        </div>
    </div>
</template>

<script>
import Election from "../../Models/Election";
import MixNetProofs from "../../components/anonymization_methods/mixnets/MixNetProofs";

export default {
    name: "ElectionProofs",
    components: {MixNetProofs},
    data() {
        return {
            election: null,
            proofs: null
        };
    },
    watch: {
        $route(to, from) {
            this.fetch_election_proofs(to.params.slug);
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_election_proofs(slug);
    },

    methods: {

        fetch_election_proofs(slug) {
            let self = this;
            Election.fetch(slug)
                .then(election => {
                    self.election = election;
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    console.log(e);
                });

            axios.get('/api/elections/' + slug + '/proofs')
                .then(response => {
                    self.proofs = response.data;
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    console.log(e);
                });

        },
    }

}
</script>

<style scoped>

</style>
