<template>

  <div>

    <h2 class="title">{{ election.name }} &mdash; Trustee {{ trustee.name }} &mdash; Check Secret Key
      <span>[
        <router-link :to="{name:'election@trustee', params : {slug: election.slug }}">
        back to trustee home
      </router-link>
      ]</span>
    </h2>

    <p>
      Your public key fingerprint is: <b>{{ trustee.public_key_hash }}</b>
    </p>

    <div id="loading">
      loading crypto functions...
    </div>

    <div id="input" style="display:none;">
      <p>
        To verify that you have the right secret key, paste it here:
      </p>
      <textarea v-model="secret_key" cols="60" rows="5" wrap="soft" style="height: 25em;"></textarea>
      <br/>
      <button @click="check_sk">Check</button>
    </div>

    <div id="processing" style="display:none;">
      checking your secret key...
    </div>

    <div id="result">
    </div>

    <div id="applet_div"></div>

  </div>

</template>

<script>

const {SHA256} = require("sha2");

export default {
  name: "TrusteeCheckSKModal",

  props: {
    trustee: {},
    election: {},
    secret_key: {},
  },

  data() {
    return {
      PK_HASH: null
    }
  },

  mounted() {
    this.reset();
    // TODO PK_HASH = "{{trustee.public_key_hash}}";

  },

  methods: {
    reset() {
      $('#processing').hide();
      $('#result').html("");
      $('#input').hide();
      $('#loading').show();
      BigInt.setup(function () {
        $('#loading').hide();
        $('#input').show();
      });
    },

    check_sk() {
      $('#input').hide();
      $('#processing').show();

      try {
        var secret_key = ElGamal.SecretKey.fromJSONObject(JSON.parse(this.secret_key));

        var pk_hash = SHA256(jQuery.toJSON(secret_key.pk)).toString("base64");
        var key_ok_p = (pk_hash == PK_HASH);
      } catch (e) {
        debugger;
        var key_ok_p = false;
      }

      $('#processing').hide();

      var reset_link = "<br /><a href='javascript:reset();'>try again</a>";
      if (key_ok_p) {
        $('#result').html("your secret key matches!");
      } else {
        $('#result').html("OH OH, your key is bad." + reset_link);
      }

      // reset
      this.secret_key = '';
    }
  }
}
</script>

<style scoped>
span {
  font-size: 0.7em;
}
</style>
