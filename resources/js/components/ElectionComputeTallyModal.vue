<template>
  <div>

    <h2 class="title">Compute Tally for Election: {{ election.name }}</h2>

    <div id="instructions">
      <div v-if="election.num_cast_votes">
        <p>
          You are about to compute the encrypted tally for election <b>{{ election.name }}</b>.
        </p>

        <p>
          Once you do this, voters will no longer be able to cast a ballot.
        </p>

        <button class="button" @click="computeTally">compute encrypted tally!</button>
        <button @click="closeModal">never mind</button>
      </div>

      <p v-else>
        No votes have been cast in this election. At least one vote must be cast before you compute the
        tally.<br/><br/>
        <a href="javascript:void(0)" @click="closeModal">back to election</a>
      </p>
    </div>

    <br/><br/>

  </div>

</template>

<script>

export default {
  name: "ElectionComputeTallyModal",

  props: {
    election: {
      required: true,
      type: Object
    }
  },

  methods: {
    computeTally() {
      let self = this;
      this.$http.post(BASE_URL+"/api/elections/" + this.election.slug + "/compute_tally")
          .then(response => {
            self.$toastr.success("Ok, tally has begun");
            self.closeModal();
          })
          .catch(e => {
            self.$toastr.error("Error");
          })
    },

    closeModal() {
      this.$emit('close');
    }
  }
}
</script>

<style scoped>

</style>
