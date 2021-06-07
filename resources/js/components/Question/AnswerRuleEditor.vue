<template>
    <div>
        <vue-query-builder :rules="rules"
                           :maxDepth="3"
                           v-model="query"/>
        <code>{{ rules_sql }}</code>
    </div>
</template>

<script>
import ExampleRuleType from "./ExampleRuleType";
import VueQueryBuilder from "vue-query-builder";

export default {
    name: "AnswerRuleEditor",

    components: {
        VueQueryBuilder
    },

    data() {
        return {
            rules_sql: "",
            query: "",
        }
    },

    inject: ['_answer_attributes', '_possible_answers'],

    watch: {
        query() {
            this.rules_sql = this.getGroupSQL(this.query);
        }
    },

    computed: {
        rules() {
            return this._answer_attributes.map(aa => {
                return {
                    // type: "text",
                    type: "custom-component",
                    id: aa.name,
                    label: aa.name,
                    operators: ['=', '<>'],
                    component: ExampleRuleType
                };
            });
        },
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
            return "(" + query.operand + " " +
                (query.operator === undefined ? "is" : query.operator) +
                " " + query.value + ")";
        },
    }
}
</script>

<style scoped>

</style>
