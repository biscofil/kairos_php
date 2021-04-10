<template>
    <div>
        <div id="questions_div">
            <p>
                To cast a vote, you will be led through the following steps.<br/>
                If you have not yet logged in, you will be asked to do so at the very end of the process.
            </p>

            <ol>
                <li>
                    <b>Select</b> your preferred options.<br/>
                    <span>You can easily navigate forwards and backwards.</span>
                </li>
                <br/>

                <li>
                    <b>Review &amp; Confirm</b> your choices.<br/>
                    <span>
                        Your choices are encrypted safely inside your browser, and you get a smart ballot tracker.<br/>
                    </span>
                </li>
                <br/>

                <li>
                    <b>Submit</b> your encrypted ballot.<br/>
                    <span>
                        You will be asked to log in to submit your encrypted ballot for tallying.
                    </span>
                </li>
            </ol>

            <div align="center">
                <button @click="show_question">Start</button>
            </div>
        </div>

    </div>
</template>

<script>

export default {
    name: "BoothElection",

    methods: {
        show_question() {
            this.$emit('ok');
        }
    }
}
</script>

<style scoped>
span {
    font-size: 14pt;
}

#questions_div {
    font-size: 1.4em;
}
</style>
