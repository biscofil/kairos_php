<template>
    <div v-if="elections">
        <h2 class="title">
            Elections you Administer
            <router-link to="/" class="brackets_around">back to home</router-link>
        </h2>
        <ul>
            <li v-for="election in elections">
                <router-link :to="{name: 'election@view', params: { slug: election.slug }}">
                    {{ election.name }}
                </router-link>
                <span> - {{ election.voter_count }} voters / {{ election.cast_votes_count }} cast votes</span>
            </li>
        </ul>
    </div>
</template>

<script>

export default {
    name: "ElectionsAdministered",

    data() {
        return {
            elections: null
        }
    },

    mounted() {
        let self = this;
        this.$http.get(BASE_URL + '/api/elections?administered=1')
            .then(response => {
                self.elections = response.data;
            })
            .catch(e => {
                console.log(e);
            })
    }
}
</script>

<style scoped>
span {
    font-size: 0.7em;
}
</style>
