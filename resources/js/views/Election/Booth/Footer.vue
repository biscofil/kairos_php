<template>

  <div>
    <span style="float:right; padding-right:20px;">
      <a target="_new" :href="mail_url">help!</a>
    </span>

    <span v-if="election.is_test_public_key">
      The public key for this election is not yet ready. This election is in preview mode only.
    </span>
    <span v-else>
       Election Fingerprint: <span id="election_hash" style="font-weight:bold;">{{ election.hash }}</span>
    </span>
  </div>

</template>

<script>
export default {
  name: "Footer",

  props: {
    election: {
      required: true,
      type: Object
    }
  },

  computed: {
    mail_url() {
      let subject = "help with election " + this.election.name;
      let body = "I need help with election " + this.election.slug;
      return "mailto:" + this.election.help_email + "?subject=" + encodeURI(subject) + "&body=" + encodeURI(body);
    }
  }
}
</script>

