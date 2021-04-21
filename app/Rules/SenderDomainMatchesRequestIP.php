<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Log;

/**
 * This rules check that the sender domain claimed in the request resolves to the IP of the client
 * Class SenderDomainMatchesRequestIP
 * @package App\Rules
 * @property string $requestIP
 */
class SenderDomainMatchesRequestIP implements Rule
{

    private string $requestIP;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $requestIP)
    {
        $this->requestIP = $requestIP;
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
        $resolvedIP = gethostbyname($value);
        Log::debug("Request IP : $this->requestIP, claimed domain IP : $resolvedIP");
        return $resolvedIP === $this->requestIP;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The specified value does not match the client IP';
    }
}
