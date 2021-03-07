<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditCreateElectionRequest;
use App\Models\Election;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
     * @return Election[]|Collection|JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->has('administered')) {
            if (!isLogged()) {
                return response()->json(["error" => "unauthenticated"], 403);
            }
            return getAuthUser()->administeredElections()->get();
        }
        if ($request->has('voted')) {
            if (!isLogged()) {
                return response()->json(["error" => "unauthenticated"], 403);
            }
            return getAuthUser()->votedElections()->get();
        }
        return Election::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(EditCreateElectionRequest $request)
    {
        return $request->store();
    }

    /**
     * @param Election $election
     * @return Election
     */
    public function show(Election $election)
    {
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
            'questions.*.question' => ['required', 'string'],
            'questions.*.min' => ['required', 'int', 'min:1'], // TODO check min
            'questions.*.max' => ['required', 'int', 'gte:questions.*.min'],
            'questions.*.answers' => ['required', 'array', 'min:2'],  // at least two answers
            'questions.*.answers.*.answer' => ['required', 'string'],
            'questions.*.answers.*.url' => ['nullable', 'url'],
        ]);

        $election->questions = $data['questions'];
        $election->save();

        return $election;

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Election $election
     * @return Response
     */
    public function destroy(Election $election)
    {
        //
    }

    /**
     * @param Election $election
     * @return Election
     * @throws Exception
     */
    public function freeze(Election $election): Election
    {
        $election->freeze();
        return $election;
    }

}
