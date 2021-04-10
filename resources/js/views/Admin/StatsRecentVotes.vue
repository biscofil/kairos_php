<template>
  <div>
    <h1>Recent Votes</h1>

    Last 24 hours

    <p v-for="election in elections">
      <b>
        <router-link :to="{ name: 'election@view', params:{slug: election.slug }}">{{ election.name }}</router-link>
      </b>
      -- {{ election.last_cast_vote }} {{ election.num_recent_cast_votes }} recently cast votes
    </p>
  </div>
</template>

<script>

export default {
  name: "StatsRecentVotes",

  data() {
    return {
      elections: null,
    }
  },

  mounted() {
    document.title = "Statistics";
    this.fetch();
  },

  methods: {

    fetch() {
      let self = this;
      this.$http.get(BASE_URL + "/api/stats/recent-votes")
          .then(response => {
            self.elections = response.data.elections
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
