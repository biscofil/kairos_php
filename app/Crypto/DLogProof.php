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
     * @param int $base
     * @return DLogProof
     */
    public static function fromArray(array $data, int $base = 16): DLogProof
    {
        return new DLogProof(
            new BigInteger($data['commitment'], $base),
            new BigInteger($data['challenge'], $base),
            new BigInteger($data['response'], $base)
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "commitment" => $this->commitment->toHex(),
            "challenge" => $this->challenge->toHex(),
            "response" => $this->response->toHex()
        ];
    }

}
