<?php

namespace App\Http\Controllers;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Enums\QuestionTypeEnum;
use App\Exceptions\NotYourElectionException;
use App\Http\Requests\EditCreateElectionRequest;
use App\Models\Answer;
use App\Models\Election;
use App\Models\PeerServer;
use App\Models\Question;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Class ElectionController
 * @package App\Http\Controllers
 */
class ElectionController extends Controller
{

    /**
     * List elections
     * TODO pagination
     * @param Request $request
     * @return Collection|Election[]
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request)
    {
        if ($request->has('administered')) {
            if (!isLogged()) {
                throw new AuthenticationException();
            }
            return getAuthUser()->administeredElections()->get();
        }
        if ($request->has('voted')) {
            if (!isLogged()) {
                throw new AuthenticationException();
            }
            return getAuthUser()->votedElections()->get();
        }
        return Election::all();
    }

    /**
     * @return array[]
     */
    public function get_editor_parameters(): array
    {
        return [
            'election_types' => [],
            'help_email' => getCurrentServer()->help_email_address,
            'is_private' => !getCurrentServer()->show_elections,
            'cryptosystems' => [
                [
                    'id' => CryptoSystemEnum::RSA,
                    'name' => 'RSA',
                    'anonymization_methods' => [
                        [
                            'id' => AnonymizationMethodEnum::DecMixNet,
                            'name' => 'Decryption MixNet'
                        ],
                        [
                            'id' => AnonymizationMethodEnum::EncMixNet,
                            'name' => 'Encryption MixNet'
                        ]
                    ]
                ], [
                    'id' => CryptoSystemEnum::ElGamal,
                    'name' => 'ElGamal',
                    'anonymization_methods' => [
//                        [
//                            'id' => AnonymizationMethodEnum::DecMixNet,
//                            'name' => 'Decryption MixNet'
//                        ],
                        [
                            'id' => AnonymizationMethodEnum::EncMixNet,
                            'name' => 'Encryption MixNet'
                        ],
                        [
                            'id' => AnonymizationMethodEnum::DecReEncMixNet,
                            'name' => 'Decryption-Re-Encryption MixNet'
                        ]
                    ]
                ], [
                    'id' => CryptoSystemEnum::ExponentialElGamal,
                    'name' => 'Exponential ElGamal',
                    'anonymization_methods' => [
                        [
                            'id' => AnonymizationMethodEnum::Homomorphic,
                            'name' => 'Homomorphic encryption'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param EditCreateElectionRequest $request
     * @return Election
     * @throws \Exception
     */
    public function store(EditCreateElectionRequest $request): Election
    {
        return $request->store();
    }

    /**
     * @param Election $election
     * @return Election
     */
    public function show(Election $election): Election
    {
        $election->load([
            'questions.answers',
            'trustees.peerServer'
        ]);
        $election->loadCount(['votes', 'mixes']);
        return $election;
    }

    /**
     * @param Request $request
     * @param Election $election
     * @return Election
     * @noinspection PhpDocSignatureInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function update(EditCreateElectionRequest $request, Election $election): Election
    {
        return $request->update();
    }

    /**
     * @param Election $election
     * @return Election
     */
    public function copy(Election $election): Election
    {
        return $election->duplicate();
    }

    /**
     * @param Election $election
     * @param Request $request
     * @return Election
     */
    public function archive(Election $election, Request $request): Election
    {
        $data = $request->validate([
            'archived' => ['required', 'bool']
        ]);
        $election->setArchived($data['archived']);
        return $election;
    }

    /**
     * @param Election $election
     * @param Request $request
     * @return Election
     */
    public function feature(Election $election, Request $request): Election
    {

        $data = $request->validate([
            'featured' => ['required', 'bool']
        ]);
        $election->setFeatured($data['featured']);
        return $election;
    }

    /**
     * @param Election $election
     * @param Request $request
     * @return Election
     */
    public function questions(Election $election, Request $request): Election
    {

        $data = $request->validate([
            'questions' => ['required', 'array', 'min:1'], // at least one question
            'questions.*.question_type' => [
                'required',
                'string',
                Rule::in(array_keys(QuestionTypeEnum::QUESTION_TYPES))
            ],
        ]);

        DB::transaction(function () use ($election, $data) {


            $election->questions->each(function (Question $question) {
                $question->answers()->delete(); // also delete answers
                $question->delete();
            });

            foreach ($data['questions'] as $question_idx => $question) {

                $qtClass = QuestionTypeEnum::getByIdentifier($question['question_type']);
                $qtClass::validateQuestionCreation($question);

                $q = new Question();
                $q->election_id = $election->id;
                $q->local_id = $question_idx + 1;
                $q->min = $question['min'];
                $q->max = $question['max'];
                $q->question = $question['question'];
                $q->question_type = $question['question_type'];
                $q->save();

                foreach ($question['answers'] as $answer_idx => $answer) {
                    $a = new Answer();
                    $a->question_id = $q->id;
                    $a->local_id = $answer_idx + 1;
                    $a->answer = $answer['answer'];
                    $a->url = $answer['url'];
                    // TODO attributes
                    $a->save();
                }

            }

        });

        $election->save();

        $election->load('questions.answers');

        return $election;

    }

    /**
     * @param Election $election
     * @return \App\Models\Election
     * @throws Exception
     */
    public function freeze(Election $election): Election
    {
        // make sure the curent server is the election creator
        if ($election->peer_server_id !== PeerServer::meID) {
            throw new NotYourElectionException();
        }
        if ($election->voting_starts_at->subMinutes(2)->lte(Carbon::now())) {
            throw new \Exception('voting_starts_at must be at least two minutes after the election freeze time.');
        }
        $election->freeze();

        $election->load('questions.answers');

        return $election;
    }

    /**
     * @param \App\Models\Election $election
     * @return array
     */
    public function proofs(Election $election)
    {
        return $election->anonymization_method->getClass()::getProofs($election);
    }

}
