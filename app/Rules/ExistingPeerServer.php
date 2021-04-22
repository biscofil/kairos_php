<?php

namespace App\Rules;

use App\Models\PeerServer;
use Illuminate\Contracts\Validation\Rule;

class ExistingPeerServer implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return PeerServer::withDomain($value)->count() > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The server has not been added to the database table yet.';
    }
}
