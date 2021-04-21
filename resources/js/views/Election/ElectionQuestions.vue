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
                        <router-link :to="{name:'election@view', params:{ slug: election.slug }}" >{{ election.name }}</router-link>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Questions</li>
                </ol>
            </nav>

            <h3 class="title">{{ election.name }} &mdash; Questions
                <router-link class="brackets_around" :to="{ name:'election@view', params:{slug:election.slug} }">
                    back to election
                </router-link>
            </h3>

            <div>
                <vue-query-builder :rules="rules"
                                   :maxDepth="3"
                                   v-model="query"></vue-query-builder>
                <pre lang="sql">{{rules_sql}}</pre>
            </div>

            <hr>

            <div>
                <fieldset v-for="(question,idx) in questions">
                    <legend>Question #{{ idx + 1 }}
                        <a href="javascript:void(0)" @click="removeQuestion(idx)"
                           class="brackets_around danger">remove</a>
                    </legend>
                    <QuestionEditor v-model="questions[idx]"/>
                </fieldset>
                <button class="small button" @click="addQuestion">Add question</button>
                <hr>
                <button @click="save_questions">Save all</button>
            </div>

        </div>
    </div>
</template>

<script>
import VueQueryBuilder from 'vue-query-builder';
import QuestionEditor from "../../components/QuestionEditor";

export default {
    name: "ElectionQuestions",

    components: {QuestionEditor, VueQueryBuilder},

    data() {
        return {
            election: null,
            questions: null,
            rules_sql: '',
            //
            rules: [
                {
                    type: "text",
                    id: "party",
                    label: "Party",
                    //operators: ['<>', '=']
                },
                {
                    type: "radio",
                    id: "gender",
                    label: "Gender",
                    choices: [
                        {label: "Male", value: "male"},
                        {label: "Female", value: "female"}
                    ],
                },
            ],
            query: null
        }
    },

    watch: {
        $route(to, from) {
            this.fetch_questions(to.params.slug);
        },
        query() {
            this.rules_sql = this.getGroupSQL(this.query);
        }
    },

    mounted() {
        let slug = this.$route.params.slug;
        this.fetch_questions(slug);
    },

    methods: {

        getSql(c) {
            if (c.type === "query-builder-group") {
                return this.getGroupSQL(c.query);
            } else {
                return this.getRuleSQL(c.query);
            }
        },

        getGroupSQL(query) {
            let self = this;
            return "(" + query.children.map(c => {
                return self.getSql(c);
            }).join(query.logicalOperator === "all" ? " AND " : " OR ") + ")";
        },

        getRuleSQL(query) {
            return "(" + query.operand + " " + query.operator + " '" + query.value + "')";
        },

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

<style>
.vqb-group {
    border: 1px solid #a2a2a2;
    padding: 10px;
    margin: 2px;
    border-radius: 5px;
}

.vqb-group-heading {
    background: #dedede;
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
