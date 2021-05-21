<?php

namespace App\Http\Requests;

use App\Enums\AnonymizationMethodEnum;
use App\Enums\CryptoSystemEnum;
use App\Models\Election;
use App\Models\PeerServer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\RequiredIf;

/**
 * Class EditCreateElectionRequest
 * @package App\Http\Requests
 * @property Election|null $electionToUpdate
 */
class EditCreateElectionRequest extends FormRequest
{
    private $electionToUpdate = null;

    public function __construct(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null)
    {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);

        $this->electionToUpdate = request()->route('election');
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!isLogged()) {
            return false;
        }
        if ($this->electionToUpdate) {
            return $this->electionToUpdate->admin_id == getAuthUser()->id;
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $creatingNew = is_null($this->electionToUpdate);
        return [

            'cryptosystem' => ['required', 'string', new In(array_keys(CryptoSystemEnum::CRYPTOSYSTEMS))],
            'anonymization_method' => ['required', 'string', new In(array_keys(AnonymizationMethodEnum::ANONYMIZATION_METHODS))],

            'name' => ['required', 'string'],
            'slug' => ['required', 'string'],
            'description' => ['required', 'string'],
            'help_email' => ['required', 'email'],
            'info_url' => ['required', 'url'],
            //
            'use_voter_alias' => ['nullable', 'bool'],
            'use_advanced_audit_features' => ['nullable', 'bool'],
            'randomize_answer_order' => ['nullable', 'bool'],
            //
            'voting_starts_at' => ['nullable', new RequiredIf($creatingNew), 'date'],
            'voting_ends_at' => ['nullable', new RequiredIf($creatingNew), 'date', 'after:voting_starts_at'],
        ];
    }

    /**
     * @return Election
     */
    public function store(): Election
    {
        $data = $this->validated();
        $election = Election::make($data);
        $election->uuid = (string)Str::uuid();
        // TODO check voting_starts_at, voting_ends_at
        $election->admin()->associate(getAuthUser());
        $election->peerServerAuthor()->associate(PeerServer::me());
        $election->save();
        return $election;
    }

    /**
     * @return Election
     */
    public function update(): Election
    {
        $data = $this->validated();
        unset($data['cryptosystem']); // can't be changed
        unset($data['anonymization_method']); // can't be changed
        $election = $this->electionToUpdate;
        // TODO check voting_starts_at, voting_ends_at
        $election->update($data);
        return $election;
    }
}
