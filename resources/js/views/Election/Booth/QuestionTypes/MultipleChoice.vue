<template>

    <div>
        <span>You can pick {{ question.min }} to {{ question.max }} answers</span>
        <div v-for="(answer,a_idx) in question.answers">
            <label>
                <input type="checkbox"
                       :name="'question_' + q_idx"
                       v-model="choices"
                       :value="a_idx"
                       :disabled="value.length >= question.max && value.indexOf(a_idx) === -1">
                {{ answer.answer }}
                <a :href="answer.url" target="_blank" class="brackets_around" v-if="answer.url">Link</a>
            </label>
        </div>
    </div>

</template>

<script>
import Question from "../../../../Models/Question";

export default {
    name: "MultipleChoice",

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

    data() {
        return {
            choices: []
        }
    },

    watch: {
        choices() {
            this.$emit('input', this.choices);
        }
    }

}
</script>

<style scoped>

</style>
