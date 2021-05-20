<?php


namespace App\Voting\CryptoSystems\ElGamal;


use phpseclib3\Math\BigInteger;

/**
 *
 * Class EGZKProof
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property Commitment commitment
 * @property BigInteger challenge
 * @property BigInteger response
 */
class EGZKProof
{

    public Commitment $commitment;
    public BigInteger $challenge;
    public BigInteger $response;

    /**
     * DLogProof constructor.
     * @param Commitment $commitment
     * @param BigInteger $challenge
     * @param BigInteger $response
     */
    public function __construct(Commitment $commitment, BigInteger $challenge, BigInteger $response)
    {
        $this->commitment = $commitment;
        $this->challenge = $challenge;
        $this->response = $response;
    }

    /**
     * generate a DDH tuple proof, where challenge generator is almost certainly EG_fiatshamir_challenge_generator
     * @param EGParameterSet $parameterSet
     * @param BigInteger $x
     * @param BigInteger $alpha
     * @param callable $challenge_generator
     * @return EGZKProof
     */
    public static function generate(EGParameterSet $parameterSet, BigInteger $x, BigInteger $alpha, callable $challenge_generator): EGZKProof
    {

        # generate random w
        $w = randomBIgt($parameterSet->g);

        $commitment_a = $parameterSet->q->modPow($w, $parameterSet->p); # A = little_g^w
        $commitment_b = $alpha->modPow($w, $parameterSet->p); # B = little_h^w

        # get challenge
        $challenge = $challenge_generator($commitment_a, $commitment_b);

        # compute response
        $response = $w->add($x->multiply($challenge))->modPow(BI1(), $parameterSet->g);

        # create proof instance
        return new EGZKProof(
            new Commitment($commitment_a, $commitment_b),
            $challenge,
            $response
        );

    }

    /**
     * Verify a DH tuple proof
     */
    public function verify()
    {

//        # check that A, B are in the correct group
//        if not (pow(self.commitment['A'], self.pk.q, self.pk.p) == 1
//            and pow(self.commitment['B'], self.pk.q, self.pk.p) == 1):
//            return False
//
//        # check that little_g^response = A * big_g^challenge
//        first_check = (pow(little_g, self.response, p) == ((pow(big_g, self.challenge, p) * self.commitment['A']) % p))
//
//        # check that little_h^response = B * big_h^challenge
//        second_check = (pow(little_h, self.response, p) == ((pow(big_h, self.challenge, p) * self.commitment['B']) % p))
//
//        # check the challenge?
//        third_check = True
//
//        if challenge_generator:
//            third_check = (self.challenge == challenge_generator(self.commitment))
//
//        return first_check and second_check and third_check

    }

}
