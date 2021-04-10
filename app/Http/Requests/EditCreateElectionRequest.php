<?php

namespace App\Http\Requests;

use App\Models\Election;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\In;

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
        return [
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
            'voting_starts_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'voting_end_at' => ['nullable', 'date_format:Y-m-d\TH:i', 'gte:voting_starts_at'],
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
        $election->admin()->associate(getAuthUser());
        $election->save();
        return $election;
    }

    /**
     * @return Election
     */
    public function update(): Election
    {
        $data = $this->validated();
        $election = $this->electionToUpdate;
        $election->update($data);
        return $election;
    }
}
