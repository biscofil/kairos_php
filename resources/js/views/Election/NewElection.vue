<template>
    <div>
        <h2 class="title">Create a New Election</h2>
        <ElectionEditor/>
    </div>
</template>

<script>

import ElectionEditor from "../../components/ElectionEditor";

export default {
    name: "NewElection",
    components: {ElectionEditor},
}
</script>
