<template>
    <div>
        <h1>Admin</h1>
        <ul>
            <li>
                <router-link :to="{'name' : 'admin@network' }">Network</router-link>
            </li>
            <li>
                <router-link :to="{'name' : 'admin@elections' }">Election stats</router-link>
            </li>
            <li>
                <router-link :to="{'name' : 'admin@recent-votes' }">Elections with recent votes</router-link>
            </li>
            <li>
                <router-link :to="{'name' : 'admin@elections-problems' }">Elections with recent problems</router-link>
            </li>
        </ul>

        <p v-if="num_votes_in_queue != null">
            <b>{{ num_votes_in_queue }}</b> votes in queue.
            <a v-if="num_votes_in_queue" href="javascript:void(0)" @click="force" class="brackets_around">force it</a>
        </p>
    </div>
</template>

<script>

export default {
    name: "Stats",

    data() {
        return {
            num_votes_in_queue: null,
        }
    },

    mounted() {
        document.title = "Statistics";
        let self = this;
        this.$http.get(BASE_URL + "/api/stats")
            .then(response => {
                self.num_votes_in_queue = response.data.num_votes_in_queue
            })
            .catch(e => {
                self.$toastr.error("Error");
            });
    },

    methods: {
        force() {
            let self = this;
            this.$http.post(BASE_URL + "/api/stats/force-queue")
                .then(response => {
                    self.$toastr.success("Ok");
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        }
    }

}
</script>

<style scoped>

</style>
