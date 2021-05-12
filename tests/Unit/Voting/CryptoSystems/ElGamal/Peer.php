<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGPrivateKey;
use App\Voting\CryptoSystems\ElGamal\EGPublicKey;
use App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast;
use App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial;
use phpseclib3\Math\BigInteger;

/**
 * Class Peer
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 * @property int $id
 * @property int $t
 * @property EGParameterSet $ps
 * @property EGThresholdPolynomial $polynomial
 * @property EGThresholdBroadcast[] $receivedBroadcasts
 * @property EGPrivateKey $sk
 * @property EGPrivateKey $skShare
 * @property EGPublicKey $pk
 * @property BigInteger[] $shareSent
 * @property BigInteger[] $receivedShares
 * @property BigInteger $privateKeyShare
 * @property int[] $qualifiedPeers
 */
class Peer
{

    public int $id;
    public int $t;
    public EGParameterSet $ps;
    public EGThresholdPolynomial $polynomial;
    public array $receivedBroadcasts = [];
    public EGPrivateKey $sk;
    public EGPrivateKey $skShare;
    public EGPublicKey $pk;
    public array $sharesSent = [];
    public array $receivedShares = [];
    public BigInteger $privateKeyShare;
    public array $qualifiedPeers = [];

    /**
     * Peer constructor.
     * @param int $id
     * @param \App\Voting\CryptoSystems\ElGamal\EGParameterSet $ps
     * @param int $t 0 <= t <= l-1
     */
    public function __construct(int $id, EGParameterSet $ps, int $t)
    {
        $this->t = $t;
        $this->id = $id;
        $this->ps = $ps;

        $kp = EGKeyPair::generate($ps);
        $this->sk = $kp->sk;
        $this->pk = $kp->pk;

        $this->polynomial = $kp->sk->getThresholdPolynomial($t);
//        dump("Peer $this->id : " . $this->polynomial->toString());
    }

    // ##########################################################
    // ##########################################################
    // ##########################################################

    /**
     * @return \App\Voting\CryptoSystems\ElGamal\EGThresholdBroadcast
     */
    public function getBroadcast(): EGThresholdBroadcast
    {
        return $this->polynomial->getBroadcast();
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
     */
    public function addQualifiedPeer(int $i): void
    {
        if (!in_array($i, $this->qualifiedPeers)) {
            $this->qualifiedPeers[] = $i;
        }
    }

    /**
     * @param \App\Voting\CryptoSystems\ElGamal\EGPublicKey $pk
     * @return \App\Voting\CryptoSystems\ElGamal\EGPrivateKey
     */
    public function computeX_j(EGPublicKey $pk): EGPrivateKey
    {
        $this->receivedShares["$this->id"] = $this->getShareToSend($this->id);
        $s = $this->getShareToSend($this->id); // self share
        $str = $s->toString();
        foreach ($this->qualifiedPeers as $qualifiedPeerID) {
            $recSh = $this->receivedShares["$qualifiedPeerID"]; //->multiply($lambda)
            $s = $s->add($recSh)->modPow(BI1(), $this->ps->p); // was q
            $str .= "+{$recSh->toString()}";
        }
//        dump("     > x_{$this->id}  = $str mod {$this->ps->p} = " . $s->toString());
        return new EGPrivateKey($pk, $s);
    }

    /**
     * @return \App\Voting\CryptoSystems\ElGamal\EGPublicKey
     * @throws \Exception
     */
    public function getCombinedPublicKey(): EGPublicKey
    {
        $s = $this->pk; // append self to qualified peers
        foreach ($this->qualifiedPeers as $qualifiedPeerID) {

            $_p = new EGPublicKey($this->ps, $this->receivedBroadcasts["$qualifiedPeerID"]->A_I_K_values[0]);
//            dump($_p->y->toString());
            $s = $_p->combine($s);
//
//            $s = $s->multiply($this->receivedBroadcasts[$qualifiedPeerID]->A_I_K_values[0]) // public key = factors[0], maybe not
//            ->modPow(BI1(), $this->ps->p);
        }
        return $s;
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
        $s = $this->polynomial->getShare($i);
        $this->shareSent["$i"] = $s;
        return $s;
    }

    /**
     * @param int $i
     * @param BigInteger $share
     */
    public function setReceivedShare(int $i, BigInteger $share): void
    {
        $this->receivedShares["$i"] = $share;
//        dump("{$this->id} has received " . $share->toString() . " from $i");
    }

    /**
     * @param int $i
     * @return bool
     */
    public function isShareValid(int $i): bool
    {
//        dump("$this->id is checking share of $i : " . $this->receivedShares["$i"]->toString());
        return $this->receivedBroadcasts[$i]->isValid(
            $this->receivedShares["$i"],
            $this->id
        );
    }
}
