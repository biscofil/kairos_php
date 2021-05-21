<template>
    <span :class="badgeClass" v-if="election.current_phase">
        {{ election.current_phase.name }}
    </span>
</template>

<script>
export default {
    name: "ElectionPhaseBadge",

    props: {
        election: {
            required: true
        }
    },

    computed: {
        badgeClass() {
            if (!this.election.current_phase) {
                return "";
            }
            return "badge badge-" + this.election.current_phase.class;
        }
    }
}
</script>

<style scoped>

</style>
