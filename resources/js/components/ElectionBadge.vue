<template>
  <div>
    <h3 v-if="show_title">{{ election.name }}</h3>
    <b>{{ election.num_cast_votes }}</b> cast votes.
    <span v-if="show_vote_link">
      [<a target="_top" :href="election_url">vote!</a>]
    </span>
    <br/>
    <span style="font-size:0.7em;">
      powered by <a target="_top" href="http://heliosvoting.org">Helios Voting</a>
  </span>
  </div>
</template>

<script>
export default {
  name: "ElectionBadge",

  data() {
    return {
      show_title: false,
      show_vote_link: false,
      election_url: ""
    }
  }
}
</script>

<style scoped>
h3 {
  padding: 0;
  margin: 0
}
</style>