<template>
    <ul class="list-group">
        <SlickList lockAxis="y" v-model="answer_ids" tag="ul">
            <SlickItem v-for="(answer, index) in sorted_answers" :index="index" :key="index" tag="li" class="list-group-item">
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
        this.answer_ids = Array.from(this.question.answers.keys());
    },

    data() {
        return {
            answer_ids: []
        };
    },

    computed : {
      sorted_answers(){
          let self = this;
          return this.value.map( idx => {
              return self.question.answers[idx];
          });
      }
    },

    watch: {
        answer_ids() {
            this.$emit('input', this.answer_ids);
        }
    }
}
</script>

<style scoped>

</style>
