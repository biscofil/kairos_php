/*
 * JavaScript HTML 5 Worker for BOOTH
 */


var console = {
    'log': function (msg) {
        self.postMessage({'type': 'log', 'msg': msg});
    }
};

var ELECTION = null;

function do_setup(message) {
    console.log("setting up worker");

    ELECTION = Election.fromJSONString(message.election);
}

function do_encrypt(message) {
    console.log("encrypting answer for question " + ELECTION.questions[message.q_num]);

    var encrypted_answer = new EncryptedAnswer(ELECTION.questions[message.q_num], message.answer, ELECTION.public_key);

    console.log("done encrypting");

    // send the result back
    self.postMessage({
        'type': 'result',
        'q_num': message.q_num,
        'encrypted_answer': encrypted_answer.toJSONObject(true),
        'id': message.id
    });
}

// receive either
// a) an election and an integer position of the question
// that this worker will be used to encrypt
// {'type': 'setup', 'election': election_json}
//
// b) an answer that needs encrypting
// {'type': 'encrypt', 'q_num': 2, 'id': id, 'answer': answer_json}
//
self.onmessage = function (event) {
    // dispatch to method
    if (event.data.type === "setup") {
        do_setup(event.data);
    } else if (event.data.type === "encrypt") {
        do_encrypt(event.data);
    }
};
