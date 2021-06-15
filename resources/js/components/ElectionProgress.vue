<template>

    <div v-if="timelineItems">

        <div class="row">
            <div class="col-sm-12">
                <h4>Timeline</h4>
                <ul class="timeline">
                    <li v-for="timelineItem in timelineItems" :class="timelineItem.class">
                        <a href="javascript:void(0)">
                            {{timelineItem.title}}
                        </a>
                        <a href="javascript:void(0)" class="float-right">{{timelineItem.datetime}}</a>
                        <p>
                            {{timelineItem.description}}
                        </p>
                    </li>
                </ul>
            </div>
        </div>

    </div>

</template>

<script>

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

    mounted() {

        if (this.election.frozen_at) {
            this.timelineItems.push({
                datetime: new Date(this.election.frozen_at),
                title: 'Freeze',
                description: 'Frozen @ ' + (new Date(this.election.frozen_at)).toDateString(),
                class: 'done'
            })
        }

        if (this.election.voting_starts_at) {
            this.timelineItems.push({
                datetime: new Date(this.election.voting_starts_at),
                title: this.election.voting_started_at ? 'Started' : 'Starts',
                description: this.election.voting_started_at
                    ? ('Started @ ' + (new Date(this.election.voting_started_at)).toDateString())
                    : ('Starts @ ' + (new Date(this.election.voting_starts_at)).toDateString()),
                class: this.election.voting_started_at ? 'done' : 'todo',
            })
        }

        if (this.election.voting_ends_at) {
            this.timelineItems.push({
                datetime: new Date(this.election.voting_ends_at),
                title: this.election.voting_ended_at ? 'Ended' : 'Ends',
                description: this.election.voting_ended_at
                    ? ('Ended @ ' + (new Date(this.election.voting_ended_at)).toDateString())
                    : ('Ends @ ' + (new Date(this.election.voting_ends_at)).toDateString()),
                class: this.election.voting_ended_at ? 'done' : 'todo',
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

<style scoped>
ul.timeline {
    list-style-type: none;
    position: relative;
}

ul.timeline:before {
    content: ' ';
    background: #d4d9df;
    display: inline-block;
    position: absolute;
    left: 29px;
    width: 3px;
    height: 100%;
    z-index: 400;
}

ul.timeline > li {
    margin: 20px 0;
    padding-left: 20px;
}

ul.timeline > li:before {
    content: ' ';
    background: white;
    display: inline-block;
    position: absolute;
    border-radius: 50%;
    border: 3px solid #d0d0d0;
    left: 20px;
    width: 20px;
    height: 20px;
    z-index: 400;
    margin-top: 10px;
}


ul.timeline > li.done:before {
    background: #499246;
}

ul.timeline > li.todo:before {
    background: #e74c3c;
}

</style>
