<?php


namespace App\Voting\CryptoSystems\ElGamal;

use phpseclib3\Math\BigInteger;

/**
 * Class DLogProof
 * @package App\Voting\CryptoSystems\ElGamal;
 * Discrete Logarithm
 * @property BigInteger commitment
 * @property BigInteger challenge
 * @property BigInteger response
 */
class DLogProof
{

    public BigInteger $commitment;
    public BigInteger $challenge;
    public BigInteger $response;

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
            BI($data['commitment'], $base),
            BI($data['challenge'], $base),
            BI($data['response'], $base)
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'commitment' => $this->commitment->toHex(),
            'challenge' => $this->challenge->toHex(),
            'response' => $this->response->toHex()
        ];
    }

}
