<template>
    <div>
        <div v-if="election == null">
            Loading election..
        </div>
        <div v-else>
            <h2 class="title">Edit an Election</h2>
            <h2 class="title">
                {{ election.name }} &mdash;
                Update
                <router-link :to="'/elections/' + election.slug" class="brackets_around">cancel</router-link>
            </h2>
            <ElectionEditor v-if="election" :election="election"/>
        </div>
    </div>
</template>

<script>
import ElectionEditor from "../../components/ElectionEditor";
import Election from "../../Models/Election";

export default {
    name: "EditElection",
    components: {
        ElectionEditor
    },

    data() {
        return {
            election: null
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
            Election.fetch(slug)
                .then(election => {
                    self.election = election
                })
                .catch(e => {
                    self.$toastr.error("Error");
                    console.log(e)
                });
        }
    }
}
</script>

<style scoped>

</style>
