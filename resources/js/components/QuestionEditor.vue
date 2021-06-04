<template>
    <div>
        <b>Question:</b>

        <div class="form-group row">
            <label class="col-sm-3">Question type</label>
            <div class="col-sm-9">
                <select class="form-control">
                    <option value="multiple_choice">Multiple choice</option>
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
                <input type="number" class="form-control" min="1" v-model="value.max">
            </div>
        </div>

        <!--        <label>-->
        <!--            Result Type:-->
        <!--            <select v-model="value.result_type">-->
        <!--                <option value="absolute">Absolute</option>-->
        <!--                <option value="relative">Relative</option>-->
        <!--            </select>-->
        <!--        </label>-->


        <div class="form-group row">
            <label class="col-sm-3">
                Answers
            </label>
            <div class="col-sm-9">

                <div v-for="(answer,idx) in value.answers" class="row">
                    <label class="col-sm-4">
                        Answer
                        <input type="text" class="form-control" v-model="value.answers[idx]['answer']" placeholder="Answer">
                    </label>
                    <label class="col-sm-4">
                        Answer's URL
                        <input type="url" class="form-control" v-model="value.answers[idx]['url']" placeholder="Url">
                    </label>
                    <div class="col-sm-4">
                        <button class="btn btn-sm btn-info" @click="removeAnswer(idx)">Remove answer</button>
                    </div>
                </div>

                <button class="btn btn-sm btn-info" @click="addAnswer">Add answer</button>

            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "QuestionEditor",

    props: {
        value: {}
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
                "answer": "",
                "url": "",
            });
            this.$emit('input', copy)
        }
    }

}
</script>

<style scoped>

</style>
