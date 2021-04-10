<template>
  <div>
    <h1 id="election_name">{{ election.name}}</h1>
    <p>&nbsp;</p>
  </div>
</template>

<script>
export default {
  name: "Header",

  props: {
    election: {
      required: true,
      type: Object
    }
  }
}
</script>

