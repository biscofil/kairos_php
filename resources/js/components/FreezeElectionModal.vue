<template>

    <div v-if="election">

        <h2 class="title">{{ election.name }} &mdash; Freeze Ballot</h2>

        <p>
            Once the ballot is frozen, the questions and options can no longer be modified.<br/>
            The list of trustees and their public keys will also be frozen.
        </p>

        <p v-if="election.openreg"><!-- TODO -->
            Registration for your election is currently <b>open</b>, which means anyone can vote, even after you freeze
            the ballot.
        </p>
        <p v-else>
            Registration for your election is currently <b>closed</b>, which means only the voters you designate will be
            able to cast a ballot. As the administrator, you will still be able to modify that voter list as theelection
            progresses.
        </p>

        <p v-if="VOTERS_EMAIL">
            You must freeze the ballot before you can contact voters.
        </p>

        <div v-if="election.issues.length">
            Before you can freeze the election, you will need to:
            <ul>
                <li v-for="issue in election.issues">
                    {{ issue.action }}
                </li>
            </ul>
            <a href="javascript:void(0)" @click="closeModal">go back to the election</a>
        </div>
        <div v-else>
            <button class="button" @click="freeze">Freeze the ballot</button>
            <button @click="closeModal">never mind</button>
        </div>

    </div>

</template>

<script>

import Election from "../Models/Election";
import {EventBus} from "../event-bus";

export default {
    name: "FreezeElectionModal",

    props: {
        election: {
            required: true,
            type: Election
        },
    },

    data() {
        return {
            VOTERS_EMAIL: null, // TODO
        }
    },

    methods: {

        freeze() {
            let self = this;
            this.election.freeze()
                .then(election => {
                    EventBus.$emit('frozenElection', election);
                    self.$toastr.success("Election frozen");
                    self.closeModal();
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        closeModal() {
            this.$emit('close');
        }

    }

}
</script>
