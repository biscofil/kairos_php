<?php


namespace App\Voting\CryptoSystems\ElGamal;


use phpseclib3\Math\BigInteger;

/**
 * Class EGDLogProof
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property EGDLogCommitment commitment (A=g^w, B=alpha^w)
 * @property BigInteger challenge (c)
 * @property BigInteger response (t)
 */
class EGDLogProof
{

    public EGDLogCommitment $commitment;
    public BigInteger $challenge;
    public BigInteger $response;

    /**
     * DLogProof constructor.
     * @param EGDLogCommitment $commitment
     * @param BigInteger $challenge
     * @param BigInteger $response
     */
    public function __construct(EGDLogCommitment $commitment, BigInteger $challenge, BigInteger $response)
    {
        $this->commitment = $commitment;
        $this->challenge = $challenge;
        $this->response = $response;
    }

    /**
     * generate a DDH tuple proof
     * @param \App\Voting\CryptoSystems\ElGamal\EGSecretKey $sk
     * @param \App\Voting\CryptoSystems\ElGamal\EGCiphertext $ciphertext
     * @param callable|null $challenge_generator
     * @return EGDLogProof
     */
    public static function generate(EGSecretKey $sk, EGCiphertext $ciphertext, ?callable $challenge_generator = null): EGDLogProof
    {

        if (is_null($challenge_generator)) {
            $challenge_generator = [EGDLogProof::class, 'DLogChallengeGenerator'];
        }

        # generate random w
        $w = $sk->pk->parameterSet->getReEncryptionFactor();

        # generate commitment A,B
        $commitment_a = $sk->pk->parameterSet->g->modPow($w, $sk->pk->parameterSet->p); # A = g ^ w mod p
        $commitment_b = $ciphertext->alpha->modPow($w, $sk->pk->parameterSet->p); # B = alpha ^ w mod p
        $commitment = new EGDLogCommitment($commitment_a, $commitment_b);

        # get challenge c
        $challenge = $challenge_generator($commitment);

        # compute response t
        $response = $w->add($sk->x->multiply($challenge))->modPow(BI1(), $sk->pk->parameterSet->p);

        # create proof instance
        return new EGDLogProof(
            $commitment,
            $challenge,
            $response
        );

    }

    /**
     * Verify a DH tuple proof
     * @param EGPublicKey $pk Public key of the claimer corresponding to the secret key used to generate the proof
     * @param \App\Voting\CryptoSystems\ElGamal\EGCiphertext $ciphertext
     * @param \App\Voting\CryptoSystems\ElGamal\EGPlaintext $plain
     * @param callable|null $challenge_generator
     * @return bool
     */
    public function isValid(EGPublicKey $pk, EGCiphertext $ciphertext, EGPlaintext $plain, ?callable $challenge_generator = null): bool
    {

        if (is_null($challenge_generator)) {
            $challenge_generator = [EGDLogProof::class, 'DLogChallengeGenerator'];
        }

        # check that A, B are in the correct group
        if (!(
            $this->commitment->a->modPow($pk->parameterSet->q, $pk->parameterSet->p)->equals(BI(1))
            && $this->commitment->b->modPow($pk->parameterSet->q, $pk->parameterSet->p)->equals(BI(1))
        )) {
            return false;
        }

        # g ^ t mod p
        $first_check_left = $pk->parameterSet->g->modPow($this->response, $pk->parameterSet->p);
        // A * y ^ challenge mod p
        $first_check_right = $this->commitment->a->multiply($pk->y->modPow($this->challenge, $pk->parameterSet->p))
            ->modPow(BI(1), $pk->parameterSet->p);
        # check that g ^ response = A * big_g ^ challenge
        $first_check = $first_check_left->equals($first_check_right);


        # little_h ^ t mod p
        $second_check_left = $ciphertext->alpha->modPow($this->response, $pk->parameterSet->p);
        // B * big_h ^ challenge
        $subGroupM = $pk->parameterSet->mapMessageIntoSubgroup($plain->m); // TODO check!!!
        $big_h = $ciphertext->beta->multiply($subGroupM->modInverse($pk->parameterSet->p))
            ->modPow(BI(1), $pk->parameterSet->p);

        $second_check_right = $this->commitment->b->multiply($big_h->modPow($this->challenge, $pk->parameterSet->p))
            ->modPow(BI(1), $pk->parameterSet->p);
        # check that little_h ^ response = B * big_h ^ challenge
        $second_check = $second_check_left->equals($second_check_right);

        # check the challenge actually matches the commitment
        $third_check = true;
        if ($challenge_generator) {
            $third_check = $this->challenge->equals($challenge_generator($this->commitment));
        }

        return $first_check && $second_check && $third_check;
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGDLogCommitment $commitment
     * @return BigInteger
     */
    public static function DLogChallengeGenerator(EGDLogCommitment $commitment): BigInteger
    {
        $array_to_hash = [];
        $array_to_hash[] = $commitment->a->toString();
        $array_to_hash[] = $commitment->b->toString();
        $string_to_hash = implode(',', $array_to_hash);
        // compute sha1 of the commitment
        return BI(sha1(utf8_encode($string_to_hash)), 16);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'commitment' => $this->commitment->toArray(),
            'challenge' => $this->challenge->toHex(),
            'response' => $this->response->toHex(),
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new static(
            EGDLogCommitment::fromArray($data['commitment']),
            BI($data['challenge'], 16),
            BI($data['response'], 16)
        );
    }

}
