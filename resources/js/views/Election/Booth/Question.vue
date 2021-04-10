<template>

  <div class="prettyform" id="answer_form" v-if="current_question">
    <input type="hidden" name="question_num" :value="question_num"/>

    <div>
      <br/>
      <b>{{ current_question.question.question }} }}</b>
      <br/>
      <span style="font-size: 0.6em;">#{{ current_question.question_num + 1 }} }} of {{ current_question.last_question_num + 1 }} }} &mdash;
       vote for
        <div v-if="current_question.question.min && current_question.question.min > 0">
            <span v-if="current_question.question.max">{{ current_question.question.min }} to {{ current_question.question.max }}</span>
            <span v-else> at least {{ current_question.question.min }}</span>
       </div>
        <div v-else>
          <span v-if="current_question.question.max && current_question.question.max > 1">
            up to {{ question.max }}
          </span>
          <span v-else> as many as you approve of</span>
        </div>
    </span>
    </div>

    <div v-for="(answer,index) in current_question.question.answers"
         id="answer_label_{ { question_num  }} }}_{ { answer_ordering[index] } }">


      <input type="checkbox" class="ballot_answer" id="answer_{ { question_num } }_{ { answer_ordering[index] } }"
             name="answer_{ { question_num } }_{ { answer_ordering[index] } }" value="yes"
             @click="click_checkbox(question_num, answer_ordering[index], this.checked);"/>

      <label class="answer"
             for="answer_{ { current_question.question_num } }_{ { current_question.answer_ordering[index] } }">
        {{ current_question.question.answers[answer_ordering[index]] }}


        <div v-if="current_question.question.answer_urls && current_question.question.answer_urls[answer_ordering[index]] &&
          question.answer_urls[answer_ordering[index]] != ''">
          &nbsp;&nbsp;
          <span style="font-size: 12pt;">
            [<a target="_blank" :href="question.answer_urls[answer_ordering[index]]"
                rel="noopener noreferrer">more info</a>]
          </span>
        </div>

      </label>

    </div>

    <div id="warning_box" v-if="warning_box_text">
      {{ warning_box_text }}
    </div>

    <div v-if="current_question.show_reviewall" style="float: right;">
      <button @click="validate_and_confirm(current_question.question_num)">Proceed</button>
    </div>

    <div v-if="current_question.question_num != 0">
      <button @click="previous(current_question.question_num)">Previous></button>
      &nbsp;
    </div>

    <div v-if="current_question.question_num < current_question.last_question_num">
      <button @click="next(current_question.question_num)">Next</button>
      &nbsp;
    </div>

    <br clear="both"/>

  </div>

</template>

<script>
export default {
  name: "Question",

  props: {
    current_question: {},
    warning_box_text: {}
  },

  methods: {
    previous(question_num) {

    },

    next(question_num) {

    },

    validate_and_confirm(question_num) {

    }
  }
}
</script>

<style scoped>
#warning_box {
  color: green;
  text-align: center;
  font-size: 0.8em;
  padding-top: 10px;
  padding-bottom: 10px;
  height: 50px;
}
</style>