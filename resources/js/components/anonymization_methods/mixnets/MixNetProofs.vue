<template>

    <div>

        <h4>Mixes</h4>
        <ul class="list-group">
            <li v-for="mix in proofs.mixes" class="list-group-item">
                <b># {{ mix.id }} <a :href="mix.download_url" target="_blank" class="brackets_around">Download</a></b>
                <span v-if="mix.previous_mix_id">
                    (previous mix: {{ mix.previous_mix_id }})
                </span><br>

                Created at: {{ mix.created_at | moment("MM/DD/YYYY HH:mm:ss") }}<br>

                Round: {{ mix.round }}<br>

                Trustee: # {{ mix.trustee_id }}<br>

                Hash: <kbd>{{ mix.hash }}</kbd><br>

                Time required:
                <span v-if="mix.mixes_generated_in" title="Mixes generated in N seconds">{{ mix.mixes_generated_in }} s</span> /
                <span v-if="mix.proofs_generated_in" title="Proofs generated in N seconds">{{ mix.proofs_generated_in }} s</span> /
                <span v-if="mix.verified_in" title="Proofs verified in N seconds">{{ mix.verified_in }} s</span><br>

                Validity:
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
