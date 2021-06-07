<template>
    <div>

        <div class="form-group row">
            <label class="col-sm-3">Question type</label>
            <div class="col-sm-9">
                <select class="form-control" v-model="value.question_type">
                    <option value="multiple_choice">Multiple choice</option>
                    <option value="stv">Single Transferable Vote</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-3">Question</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" v-model="value.question" placeholder="Question">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-3">
                Min
            </label>
            <div class="col-sm-9">
                <input type="number" class="form-control" min="0" v-model="value.min">
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-3">
                Max
            </label>
            <div class="col-sm-9">
                <input type="number" class="form-control" :min="Math.max(1, value.min)" v-model="value.max">
            </div>
        </div>

        <!--        <label>-->
        <!--            Result Type:-->
        <!--            <select v-model="value.result_type">-->
        <!--                <option value="absolute">Absolute</option>-->
        <!--                <option value="relative">Relative</option>-->
        <!--            </select>-->
        <!--        </label>-->

<!--        <div class="form-group row">-->
<!--            <div class="col-sm-3">-->
<!--                <label>-->
<!--                    Answer attributes-->
<!--                </label>-->
<!--                <button class="btn btn-sm btn-info" @click="addAnswerAttribute">Add</button>-->
<!--            </div>-->
<!--            <div class="col-sm-9">-->
<!--                <div class="form-group row" v-for="(answer_attribute,idx) in answer_attributes"-->
<!--                     v-if="!answer_attribute.read_only">-->
<!--                    <label class="col">-->
<!--                        Name-->
<!--                        <input type="text" class="form-control" v-model="answer_attribute.name"-->
<!--                               :readonly="answer_attribute.prevent_deletion"-->
<!--                               :disabled="answer_attribute.prevent_deletion">-->
<!--                    </label>-->
<!--                    <div class="col">-->
<!--                        Type-->
<!--                        <select class="form-control" v-model="answer_attribute.type"-->
<!--                                :readonly="answer_attribute.prevent_deletion"-->
<!--                                :disabled="answer_attribute.read_only">-->
<!--                            <option value="text">Text</option>-->
<!--                            <option value="enum">Enum</option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                    <div class="col">-->
<!--                        <button v-if="!answer_attribute.prevent_deletion" class="btn btn-sm btn-info"-->
<!--                                @click="removeAttribute(idx)">-->
<!--                            Remove attribute-->
<!--                        </button>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

        <div class="form-group row">
            <div class="col-sm-3">
                <label>
                    Answers
                </label>
                <button class="btn btn-sm btn-info" @click="addAnswer">Add</button>
            </div>

            <div class="col-sm-9">

                <div v-for="(answer,aidx) in value.answers" class="row">

                    <label class="col" v-for="(answer_attribute,aa_idx) in answer_attributes">
                        {{ answer_attribute.name }}
                        <input :type="answer_attribute.type"
                               class="form-control"
                               v-model="value.answers[aidx][answer_attribute.name]"
                               :readonly="answer_attribute.read_only"
                               :disabled="answer_attribute.read_only"
                               :placeholder="answer_attribute.name ">
                    </label>

                    <!--                    <label class="col-sm-4">-->
                    <!--                        Answer-->
                    <!--                        <input type="text" class="form-control" v-model="value.answers[idx]['answer']"-->
                    <!--                               placeholder="Answer">-->
                    <!--                    </label>-->
                    <!--                    <label class="col-sm-4">-->
                    <!--                        Answer's URL-->
                    <!--                        <input type="url" class="form-control" v-model="value.answers[idx]['url']" placeholder="Url">-->
                    <!--                    </label>-->
                    <div class="col">
                        <button class="btn btn-sm btn-info" @click="removeAnswer(aidx)">Remove answer</button>
                    </div>
                </div>

            </div>
        </div>

<!--        <div class="form-group row">-->
<!--            <label class="col-sm-3">-->
<!--                Answer constraints-->
<!--            </label>-->
<!--            <div class="col-sm-9">-->
<!--                <div class="row">-->
<!--                    <div v-for="(possible_answer_id,idx) in possible_answers" class="col-sm-12">-->
<!--                        Answer {{ possible_answer_id }}<br>-->

<!--                        <AnswerRuleEditor></AnswerRuleEditor>-->

<!--                        <hr>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->

        <code v-if="value.tally_query">
            {{ value.tally_query }}
        </code>

    </div>
</template>

<script>
import AnswerRuleEditor from "./AnswerRuleEditor";

export default {
    name: "QuestionEditor",

    components: {AnswerRuleEditor},

    props: {
        value: {} // question
    },

    data() {
        return {
            answer_attributes: [
                {
                    name: 'local_id',
                    prevent_deletion: true,
                    read_only: true
                },
                {
                    name: 'answer',
                    prevent_deletion: true,
                    type: 'text',
                    read_only: false
                },
                {
                    name: 'url',
                    prevent_deletion: true,
                    type: 'text',
                    read_only: false
                }
            ],
        }
    },

    mounted(){
      // TODO fetch question types
    },

    provide() {
        return {
            _answer_attributes: this.answer_attributes,
            _possible_answers: this.possible_answers
        };
    },

    computed: {
        possible_answers() {
            return Array.from({length: this.value.max}, (x, i) => i + 1);
        }
    },

    methods: {

        removeAnswer(idx) {
            let copy = Object.assign({}, this.value);
            copy.answers.splice(idx, 1);
            this.$emit('input', copy)
        },

        addAnswer() {
            let copy = Object.assign({}, this.value);
            copy.answers.push({
                "id": this.value.answers.length + 1,
                "answer": "",
                "url": "",
            });
            this.$emit('input', copy)
        },

        removeAttribute(idx) {
            this.answer_attributes.splice(idx, 1);
        },

        addAnswerAttribute() {
            this.answer_attributes.push({
                name : "New attribute",
                type : "text",
                prevent_deletion: false,
                read_only: false
            });
        }

    }

}
</script>

<style>
.vqb-group {
    border: 1px solid #a2a2a2;
    margin: 2px;
    border-radius: 5px;
}

.match-type-container {
    padding: 5px;
}

.vqb-group-heading {
    background: #9e9e9e;
    padding: 5px;
    border-radius: 5px;
}

.vqb-rule {
    border: 1px solid #a2a2a2;
    padding: 10px;
    margin: 2px;
    border-radius: 5px;
}
</style>

