<template>

  <div>

    <h2 class="title">{{ election.name }} &mdash; Audited Ballots <span style="font-size:0.7em;">[<a
        href="{% url election@view election_slug=election.slug %}">back to election</a>]</span></h2>

    <p>
      When you prepare a ballot with Helios, you immediately receive a smart ballot tracker. Before you choose to cast
      that ballot, you have the option to ask Helios to "break open" that encrypted ballot and verify that Helios
      encrypted your ballot correctly. Once that's done, you can post that opened ballot here, on the audited ballots'
      list, for everyone to verify (your identity is not included). Once you've done this, you have to re-encrypt your
      choices and obtain a different smart ballot tracker. This helps reduce the chance that someone might coerce you
      to vote differently from your true choice.
    </p>

    <p>
      These ballots are <span>not cast</span>, and they will not be counted. They are just here for auditing purposes, to
      spot-check that Helios is properly encrypting voter's choices.
    </p>

    <p>
      To verify an audited ballot, copy its entire content and paste it in the
      <a target="_new" href="/booth/single-ballot-verify.html?election_url={% url election@home election.slug %}">
        single ballot verifier
      </a>.
    </p>

    <div v-if="audited_ballots">

      <p>
        Audited Ballots {{ offset_plus_one }} - {{ offset_plus_limit }} &nbsp;&nbsp;
      </p>

      <a v-if="next_after" href="?after= next_after &offset= offset_plus_limit ">next {{ limit }}</a> &nbsp;&nbsp;

      <a v-if="offset>0" href="?">back to start</a> &nbsp;&nbsp;

      <a v-if="more_p" href="?after= next_after &offset= next_offset ">next {{ limit }}</a>

      <table class="pretty">
        <tr v-for="ballot in audited_ballots">
          <td>
            <tt style="font-size: 1.4em;">{{ ballot.vote_hash }}</tt>
            [<a target="_blank" href="?vote_hash={ { ballot.vote_hash|urlencode } }">view</a>]
          </td>
        </tr>
      </table>
    </div>

    <span v-else>no audited ballots yet</span>

  </div>

</template>

<script>

export default {
  name: "ElectionAuditedBallots",

  data() {
    return {
      election: null,
      trustees: null,
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
      this.$http.get(BASE_URL + '/api/elections/' + slug + '/audited-ballots')
          .then(response => {
            self.election = response.data.election;
            document.title = "Audited Ballots for " + self.election.name;
          })
          .catch(e => {
            self.$toastr.error("Error");
          });
    },
  }
}
</script>

<style scoped>

</style>
