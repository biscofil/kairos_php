<?php


namespace App\Voting\CryptoSystems\ElGamal;

use phpseclib3\Math\BigInteger;

/**
 * Class DLogProof
 * @package App\Voting\CryptoSystems\ElGamal;
 * Discrete Logarithm, ZKProof
 * @property BigInteger commitment (A=g^w)
 * @property BigInteger challenge (c)
 * @property BigInteger response (t)
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

    // ########################################################################################

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
     * Returns the BigInteger computed from the sha1 hashing of the
     * commitment (in base 10) encoded in UTF-8
     * @param BigInteger $commitment
     * @return BigInteger
     */
    public static function DLogChallengeGenerator(BigInteger $commitment): BigInteger
    {
        $string_to_hash = $commitment->toString();
        return BI(sha1(utf8_encode($string_to_hash)), 16);
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

    // ########################################################################################

    /**
     * Prover generates w, a random integer modulo q, and computes commitment = g^w mod p.
     * Verifier provides challenge modulo q.
     * Prover computes response = w + x * challenge mod q, where x is the secret key.
     * @param \App\Voting\CryptoSystems\ElGamal\EGSecretKey $sk
     * @param callable $challenge_generator
     * @return static
     */
    public static function generate(EGSecretKey $sk, callable $challenge_generator): DLogProof
    {
        // pick w
        $w = randomBIgt($sk->pk->parameterSet->q);

        // commitment = A = g ^ w mod p
        $commitment = $sk->pk->parameterSet->g->modPow($w, $sk->pk->parameterSet->p);

        // challenge c = hash (A)
        /** @var BigInteger $challenge */
        $challenge = $challenge_generator($commitment);
        // challenge = challenge mod g
        $challenge = $challenge->modPow(BI1(), $sk->pk->parameterSet->p);

        // t = w + x * challenge mod q, where x is the secret key.
        $response = $w->add(
            $sk->x->multiply($challenge)
        )->powMod(BI1(), $sk->pk->parameterSet->p);

        return new static($commitment, $challenge, $response);
    }

    /**
     * verify the proof of knowledge of the secret key g^response = commitment * y^challenge
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $pk
     * @param callable $challenge_generator
     * @return bool
     */
    public function verify(EGPublicKey $pk, callable $challenge_generator): bool
    {

        // g ^ t mod p
        $left_side = $pk->parameterSet->g->modPow($this->response, $pk->parameterSet->p);

        // (A * y^c) mod p
        $right_side = $this->commitment
            ->multiply($pk->y->modPow($this->challenge, $pk->parameterSet->p))
            ->modPow(BI1(), $pk->parameterSet->p);

        // hash(A) mod g
        /** @var BigInteger $expected_challenge */
        $expected_challenge = $challenge_generator($this->commitment)->modPow(BI1(), $pk->parameterSet->p);

        return $left_side->equals($right_side)
            && $this->challenge->equals($expected_challenge); // check c = hash(A)

    }

}
