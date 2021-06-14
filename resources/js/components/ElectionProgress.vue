<template>
    <Timeline
        :timeline-items="timelineItems"
        :message-when-no-items="messageWhenNoItems"
        :uniqueTimeline="true"
        :uniqueYear="true"/>
</template>

<script>

import Timeline from 'timeline-vuejs';
import Election from "../Models/Election";

var moment = require('moment'); // require

export default {
    name: "ElectionProgress",

    props: {
        election: {
            required: true,
            type: Election,
        }
    },

    components: {
        Timeline
    },

    mounted() {

        if (this.election.frozen_at) {
            this.timelineItems.push({
                from: new Date(this.election.frozen_at),
                showDayAndMonth: true,
                title: 'Freeze',
                description: 'Frozen @ ' + (new Date(this.election.frozen_at)).toDateString(),
                color: '#499246'
            })
        }

        if (this.election.voting_starts_at) {
            this.timelineItems.push({
                from: new Date(this.election.voting_starts_at),
                showDayAndMonth: true,
                title: this.election.voting_started_at ? 'Started' : 'Starts',
                description: this.election.voting_started_at
                    ? ('Started @ ' + (new Date(this.election.voting_started_at)).toDateString())
                    : ('Starts @ ' + (new Date(this.election.voting_starts_at)).toDateString()),
                color: this.election.voting_started_at ? '#499246' : '#e74c3c',
            })
        }

        if (this.election.voting_ends_at) {
            this.timelineItems.push({
                from: new Date(this.election.voting_ends_at),
                showDayAndMonth: true,
                title: this.election.voting_ended_at ? 'Ended' : 'Ends',
                description: this.election.voting_ended_at
                    ? ('Ended @ ' + (new Date(this.election.voting_ended_at)).toDateString())
                    : ('Ends @ ' + (new Date(this.election.voting_ends_at)).toDateString()),
                color: this.election.voting_ended_at ? '#499246' : '#e74c3c',
            })
        }

    },

    data() {
        return {
            messageWhenNoItems: 'There are not items',
            timelineItems: []
        };
    },

}
</script>

<style>
@import 'timeline-vuejs/dist/timeline-vuejs.css';
</style>
