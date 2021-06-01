<template>

    <div>

        <h4>Mixes</h4>
        <ul class="list-group">
            <li v-for="mix in proofs.mixes" class="list-group-item">
                <b># {{ mix.id }}</b> (previous mix: {{ mix.previous_mix_id }})<br>
                Round: {{ mix.round }}<br>
                Trustee: # {{ mix.trustee_id }}<br>
                Hash: <kbd>{{ mix.hash }}</kbd><br>
                Valid:
                <span class="badge badge-success" v-if="mix.is_valid === true">Valid</span>
                <span class="badge badge-danger" v-else-if="mix.is_valid === false">Invalid</span>
                <span class="badge badge-warning" v-else>To validate</span>
            </li>
        </ul>

    </div>

</template>

<script>
export default {
    name: "MixNetProofs",

    props: {
        proofs: {
            required: true,
        }
    }
}
</script>

<style scoped>

</style>
