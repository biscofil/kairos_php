<template>
    <ul class="list-group">
        <SlickList lockAxis="y" v-model="items" tag="ul">
            <SlickItem v-for="(answer, index) in items" :index="index" :key="index" tag="li" class="list-group-item">
                {{ answer.answer }}
                <a :href="answer.url" target="_blank" class="brackets_around" v-if="answer.url">Link</a>
            </SlickItem>
        </SlickList>
    </ul>
</template>

<script>
import {SlickItem, SlickList} from 'vue-slicksort';
import Question from "../../../../Models/Question";

export default {
    name: "STV",

    components: {
        SlickItem,
        SlickList
    },

    props: {
        question: {
            required: true,
            type: Question
        },

        q_idx: {
            required: true,
            type: Number
        },

        value: { //picked_answers[question.id]
            required: true,
            type: Array
        }
    },

    mounted() {
        this.items = this.question.answers;
    },

    data() {
        return {
            items: []
        };
    },

    watch: {
        items() {
            this.$emit('input', this.items);
        }
    }
}
</script>

<style scoped>

</style>
