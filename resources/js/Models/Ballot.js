export default class Ballot {

    /**
     *
     * @param election : Election
     * @param ballot : Ballot
     * @return {*[]}
     */
    pretty_choices(election, ballot) {
        let questions = election.questions;
        let answers = ballot.answers;

        // process the answers
        return questions.map(function (q, q_num) {
            return answers[q_num].map(function (ans) {
                return questions[q_num].answers[ans];
            });
        });
    };
}
