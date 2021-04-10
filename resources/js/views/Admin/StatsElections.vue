<template>

  <div>

    <h1>Elections</h1>

    <div>
      <form method="get" action="{% url admin@elections %}">
        <b>search</b>: <input type="text" name="q" :value="q"/>
        <input class="small button" type="submit" value="search"/> <a class="small button" href="?">clear search</a>
      </form>
    </div>

    <p>
      <a v-if="page.has_previous"
         href="?page={ { page.previous_page_number } }&limit={ { limit } }&q={ { q|urlencode } }">previous
        {{ limit }}</a>
      &nbsp;&nbsp;

      Elections {{ page.start_index }} - {{ page.end_index }} (of {{ total_elections }})&nbsp;&nbsp;

      <a v-if="page.has_next"
         href="?page={ { page.next_page_number } }&limit={ { limit } }&q={ { q|urlencode }}">
        next {{ limit }}
      </a>
      &nbsp;&nbsp;
    </p>

    <p v-for="election in elections">
      <b>
        <router-link :to="{ name: 'election@view', params:{slug: election.slug }}">{{ election.name }}</router-link>
      </b>
      by <a :href="'mailto:'+ election.admin_info_email ">{{ election.admin.name }}</a>
      -- {{ election.num_voters }} voters / {{ election.num_cast_votes }} cast votes
    </p>


  </div>

</template>

<script>

export default {
  name: "StatsElections",

  data() {
    return {
      'elections': null,
      'page': null,
      'limit': 25,
      'total_elections': null,
      'q': ""
    }
  },

  mounted() {
    document.title = "Statistics";
    this.fetch();
  },

  methods: {

    fetch(page = 1) {
      let self = this;
      this.$http.get(BASE_URL + "/api/stats/elections?page=" + page + "&limit=" + this.limit)
          .then(response => {
            self.elections = response.data.elections
            self.page = response.data.page
            self.limit = response.data.limit
            self.total_elections = response.data.total_elections
            self.q = response.data.q
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
