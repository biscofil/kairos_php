<?php


namespace App\Crypto;

use phpseclib3\Math\BigInteger;

/**
 * Class DLogProof
 * @package App\Crypto
 * @property BigInteger commitment
 * @property BigInteger challenge
 * @property BigInteger response
 */
class DLogProof
{

    public $commitment;
    public $challenge;
    public $response;

    /**
     * DLogProof constructor.
     * @param BigInteger $commitment
     * @param BigInteger $challenge
     * @param BigInteger $response
     */
    public function __construct(BigInteger $commitment, BigInteger $challenge, BigInteger $response)
    {
        $this->commitment = $commitment;
        $this->challenge = $challenge;
        $this->response = $response;
    }

    /**
     * @param array $data
     * @return DLogProof
     */
    public static function fromArray(array $data): DLogProof
    {
        return new DLogProof(
            new BigInteger($data['commitment']),
            new BigInteger($data['challenge']),
            new BigInteger($data['response'])
        );

    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "commitment" => $this->commitment->toString(),
            "challenge" => $this->challenge->toString(),
            "response" => $this->response->toString()
        ];
    }

}
