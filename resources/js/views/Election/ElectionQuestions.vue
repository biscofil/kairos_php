<template>
    <div>
        <div v-if="election == null">
            Loading...
        </div>
        <div v-else>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">Elections</a></li>
                    <li class="breadcrumb-item">
                        <router-link :to="{name:'election@view', params:{ slug: election.slug }}">{{
                                election.name
                            }}
                        </router-link>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Questions</li>
                </ol>
            </nav>

            <h3 class="title">{{ election.name }} &mdash; Questions</h3>

            <div>
                <fieldset v-for="(question,idx) in questions">
                    <legend>Question #{{ idx + 1 }}
                        <a href="javascript:void(0)" @click="removeQuestion(idx)"
                           class="brackets_around danger">remove</a>
                    </legend>
                    <QuestionEditor v-model="questions[idx]"/>
                </fieldset>
                <button class="btn btn-sm btn-info" @click="addQuestion">Add question</button>
                <hr>
                <button class="btn btn-success" @click="save_questions">Save all</button>
            </div>

        </div>
    </div>
</template>

<script>
import QuestionEditor from "../../components/Question/QuestionEditor";

export default {
    name: "ElectionQuestions",

    components: {QuestionEditor},

    data() {
        return {
            election: null,
            questions: null,
        }
    },

    watch: {
        $route(to, from) {
            this.fetch_questions(to.params.slug);
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_questions(slug);
    },

    methods: {


        fetch_questions(election_slug) {
            let self = this;
            this.$http.get(BASE_URL + '/api/elections/' + election_slug)
                .then(response => {
                    self.election = response.data;
                    let _questions = self.election.questions; // TODO JSON.parse();
                    self.questions = _questions ? _questions : [];
                })
                .catch(e => {
                    console.log(e);
                    self.$toastr.error("Error");
                })
        },

        save_questions() {
            let self = this;
            console.log(self.questions);
            this.$http.put(BASE_URL + '/api/elections/' + this.election.slug + "/questions",
                {'questions': self.questions})
                .then(response => {
                    self.questions = response.data.questions;
                    self.$toastr.success("Ok");
                })
                .catch(e => {
                    self.$toastr.error("Error");
                });
        },

        addQuestion() {
            this.questions.push({
                question: "question " + (this.questions.length + 1),
                answers: [],
                min: 0,
                max: 1,
                result_type: 'absolute'
            })
        },

        removeQuestion(idx) {
            this.questions.splice(idx, 1);
        }

    }

}
</script>

<style scoped>
fieldset {
    margin: 20px;
}
</style>
