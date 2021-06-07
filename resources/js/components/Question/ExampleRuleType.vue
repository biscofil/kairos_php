<template>
    <div>
        <select class="form-control" v-model="answer">
            <option v-for="_possible_answer in _possible_answers" :value="_possible_answer">
                Answer #{{ _possible_answer }}
            </option>
        </select>
        <select v-model="answer_attribute" class="form-control">
            <option v-for="answer_attribute in _answer_attributes" :value="answer_attribute">
                {{ answer_attribute.name }}
            </option>
        </select>
    </div>
</template>

<script>
export default {
    name: "ExampleRuleType",

    props: {
        value: {}
    },

    inject: ['_answer_attributes', '_possible_answers'],

    data() {
        return {
            answer: null,
            answer_attribute: null
        };
    },

    watch: {
        answer() {
            this.done();
        },
        answer_attribute() {
            this.done();
        }
    },

    methods: {
        done() {
            this.$emit('input', this.answer + "." + this.answer_attribute.name);
        }
    }

}
</script>

<style scoped>

</style>
