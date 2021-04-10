<template>
  <div v-if="election">
    <h2 class="title">{{ election.name }} &mdash; Extend Voting
      <span style="font-size:0.7em;">
        <router-link :to="{ name: 'election@view', params: {slug: election.slug }}" class="brackets_around">
          cancel
        </router-link>
      </span>
    </h2>

    <input type="datetime-local" v-model="newdate">

    <button @click="extend">
      Extend Voting
    </button>

  </div>
</template>

<script>

export default {
  name: "ExtendElection",

  data() {
    return {
      election: null,
      newdate: null
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
      this.$http.get(BASE_URL + '/api/elections/' + slug)
          .then(response => {
            self.election = response.data.election
            self.newdate = self.voting_extended_until
          })
          .catch(e => {
            self.$toastr.error("Error");
          });
    },

    extend() {
      let self = this;
      this.$http.post(BASE_URL + "/api/elections/" + this.election.slug + "/extend", {
        voting_extended_until: this.newdate.replace("T", " ")
      })
          .then(response => {
            self.$toastr.success("OK");
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
