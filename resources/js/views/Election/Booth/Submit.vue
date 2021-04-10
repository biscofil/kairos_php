<template>

    <div>

        <h3>Submit Your Encrypted Ballot</h3>

        <p>
            All information, other than your encrypted ballot,<br/>
            has been removed from memory.
        </p>

        <p>
            As a reminder, your ballot tracking number is:<br/>
            <b><tt style="font-size: 16pt;">{$T.encrypted_vote_hash}</tt></b>
        </p>

        <p>
            According to the election definition file, this ballot will be submitted to:
        </p>

        <p><b><tt style="font-size:1.3em;">{{ cast_url }}</tt></b></p>

        <p>
            where you will log in to validate your eligibility to vote.
        </p>

        <form method="POST" onsubmit="BOOTH.do_done();" action="{{ election.cast_url }}" id="send_ballot_form"
              class="prettyform">
            <input type="hidden" name="election_slug" value="{{ election.slug }}"/>
            <input type="hidden" name="election_hash" value="{{ election.hash }}"/>
            <textarea name="encrypted_vote" style="display: none;">
                {{encrypted_vote_json}}
              </textarea>
            <div id="submit_button">
                <label for="submit">&nbsp;</label>
                <input type="submit" value="Submit Ballot" class="pretty"/>
            </div>
        </form>

    </div>

</template>

<script>
export default {
    name: "Submit",

    props: {
        election: {}
    },

    methods: {
        do_done() {
        }
    }
}
</script>
