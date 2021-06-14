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
                <li class="breadcrumb-item active" aria-current="page">Votes</li>
            </ol>
        </nav>

        <h3>Votes</h3>

        <div class="card">
            <div class="card-header">Votes</div>
            <div class="card-body">

                <div class="table-responsive">
                    <Vuetable
                        ref="vuetable"
                        :api-url="apiUrl"
                        :fields="fields"
                        :sort-order="sortOrder"
                        :per-page="20"
                        pagination-path=""
                        :css="css.table"
                        @vuetable:pagination-data="onPaginationData">

                        <template slot="vote" slot-scope="props">
                            <VueObjectView :value="props.rowData.vote"/>
                        </template>

                    </Vuetable>
                </div>

            </div>

            <div class="card-footer">

                <div class="row">

                    <div class="col-sm-6">
                        <div class="pagination ui basic segment grid">
                            <VuetablePagination
                                ref="pagination"
                                @vuetable-pagination:change-page="onChangePage"
                                :css="css.pagination"/>
                        </div>
                    </div>


                    <div class="col-sm-6">
                        <div class="pagination ui basic segment grid">
                            <VuetablePaginationInfo
                                ref="paginationInfo"
                                info-template="Visualizzo da {from} a {to} di {total} totali"
                                no-data-template="Nessun record da mostrare"
                                :css="css.paginationInfo"/>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</template>

<script>

import Vuetable from 'vuetable-3'
import VuetablePagination from "vuetable-3/src/components/VuetablePagination";
import VuetablePaginationInfo from "vuetable-3/src/components/VuetablePaginationInfo";
import Utils from "../../Models/Utils/Utils";
import Election from "../../Models/Election";
import VueObjectView from 'vue-object-view/VueObjectView'

export default {

    name: "ElectionVotes",

    components: {
        Vuetable,
        VuetablePagination,
        VuetablePaginationInfo,
        VueObjectView
    },

    data() {
        return {
            slug: null,
            election: null,
            votes: null,
            fields: [
                {
                    name: 'id',
                    title: 'ID',
                    sortField: 'id',
                }, {
                    name: 'voter_id',
                    title: 'Voter ID',
                    sortField: 'voter_id',
                }, {
                    name: 'vote',
                    title: 'Vote',
                }
            ],

            sortOrder: [
                {
                    field: "id",
                    direction: "asc"
                }
            ],
            css: Utils.defaultVuetableCss()
        }
    },

    computed: {
        apiUrl() {
            return '/api/elections/' + this.slug + '/votes';
        }
    },

    mounted() {
        this.slug = this.$route.params.slug;
        this.fetch_election();
    },

    watch: {
        $route(to, from) {
            this.slug = to.params.slug;
            this.fetch_election();
        }
    },

    methods: {

        fetch_election() {
            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + this.slug + '/trustees')
                .then(response => {
                    self.election = Election.fromJSONObject(response.data);
                    document.title = "Trustees for " + self.election.name;
                })
                .catch(e => {
                    console.log(e);
                    self.$toastr.error("Error");
                });
        },


        onChangePage(page) {
            this.$refs.vuetable.changePage(page);
        },

        onPaginationData(paginationData) {
            this.$refs.pagination.setPaginationData(paginationData);
            this.$refs.paginationInfo.setPaginationData(paginationData);
        },
    }

}
</script>

<style scoped>

</style>
