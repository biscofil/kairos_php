<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast;
use App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial;
use phpseclib3\Math\BigInteger;

/**
 * Class Peer
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 * @property int $id
 * @property EGParameterSet $ps
 * @property EGThresholdPolynomial $polynomial
 * @property EGThresholdBroadcast[] $receivedBroadcasts
 * @property BigInteger $secret_x
 * @property BigInteger $public_y
 * @property BigInteger[] $shareSent
 * @property BigInteger[] $shareReceived
 */
class Peer
{

    public int $id;
    public EGParameterSet $ps;
    public EGThresholdPolynomial $polynomial;
    public array $receivedBroadcasts = [];
//    public BigInteger $secret_x;
//    public BigInteger $public_y;
    public array $shareSent = [];
    public array $shareReceived = [];

    /**
     * Peer constructor.
     * @param int $id
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
     * @param int $t 0 <= t <= l-1
     */
    public function __construct(int $id, EGParameterSet $ps, int $t)
    {
        dump("Creating peer #$id");
        $this->id = $id;
        $this->ps = $ps;

        $this->polynomial = $this->generatePolynomial($t);
        dump("  >> f_$id(x)=" . $this->polynomial->toString());

//        $this->secret_x = $this->polynomial->compute(BI(0));
//        dump(" > my secret value X = " . $this->secret_x->toString());
//        $this->public_y = $keyPair->pk->g->powMod($this->secret_x, $keyPair->pk->p); // TODO was q
//        dump(" > my public value Y = " . $this->public_y->toString());
    }


    /**
     * @param int $t 0 <= t <= l-1
     * @return EGThresholdPolynomial
     */
    public function generatePolynomial(int $t): EGThresholdPolynomial
    {
        return EGThresholdPolynomial::random($t, $this->ps);
    }

    // ##########################################################
    // ##########################################################
    // ##########################################################

    /**
     * @return \App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast
     */
    public function getBroadcast(): EGThresholdBroadcast
    {
        $b =  $this->polynomial->getBroadcast();
        dump("{$this->id} is broadcasting " . $b->toString());
        return $b;
    }

    /**
     * @param int $i
     * @param \App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast $broadcast
     */
    public function setReceivedBroadcast(int $i, EGThresholdBroadcast $broadcast): void
    {
        $this->receivedBroadcasts["$i"] = $broadcast;
    }

    // ##########################################################
    // ##########################################################
    // ##########################################################

    /**
     * @param int $i
     * @return BigInteger
     */
    public function getShareToSend(int $i): BigInteger
    {
        $s = $this->polynomial->compute(BI($i))->modPow(BI1(), $this->ps->g);
        $this->shareSent["$i"] = $s;
        return $s;
    }

    /**
     * @param int $i
     * @param BigInteger $share
     */
    public function setReceivedShare(int $i, BigInteger $share): void
    {
        $this->shareReceived["$i"] = $share;
//        dump("{$this->id} has received " . $share->toString() . " from $i");
    }

    /**
     * @param int $i
     * @return bool
     */
    public function isShareValid(int $i): bool
    {
        dump("$this->id is checking share of $i : " . $this->shareReceived[$i]->toString());
        return $this->receivedBroadcasts[$i]->isValid(
            $this->shareReceived["$i"],
            $this->id
        );
    }
}
