<template>
  <div>

    <h1>Problematic Elections</h1>

    Unfrozen for more than a day.

    <p v-for="election in elections">
      <b>
        <router-link :to="{ name: 'election@view', params:{slug: election.slug }}">{{ election.name }}</router-link>
      </b>
      -- {{ election.num_voters }} voters
    </p>

  </div>
</template>

<script>

export default {
  name: "StatsProblemElections",

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
      this.$http.get(BASE_URL + "/api/stats/problem-elections")
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
