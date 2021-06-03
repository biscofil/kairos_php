<template>
    <div>
        <b>Question:</b>
        <input type="text" class="form-control" v-model="value.question" placeholder="Question">

        <label>
            Select between <input type="number" class="form-control" min="0" v-model="value.min">
        </label>

        <label>
            and <input type="number" class="form-control" min="1" v-model="value.max"> answers.
        </label><br>

        <!--        <label>-->
        <!--            Result Type:-->
        <!--            <select v-model="value.result_type">-->
        <!--                <option value="absolute">Absolute</option>-->
        <!--                <option value="relative">Relative</option>-->
        <!--            </select>-->
        <!--        </label>-->

        <br>

        <b>Answers:</b>
        <div>
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
        </div>
        <button class="btn btn-sm btn-info" @click="addAnswer">Add answer</button>
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
