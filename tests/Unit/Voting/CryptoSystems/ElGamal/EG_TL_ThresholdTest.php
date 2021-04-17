<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial;
use phpseclib3\Math\BigInteger;
use Tests\TestCase;

/**
 * Class EG_TL_ThresholdTest
 * @package Tests\Unit\Voting\CryptoSystems\ElGamal
 */
class EG_TL_ThresholdTest extends TestCase
{

    /**
     * @test
     */
    public function random()
    {
        $parameterSet = new EGParameterSet(BI(10), BI(10), BI(10));

        $t = 4;
        $p = EGThresholdPolynomial::random($t, $parameterSet);
        $this->assertCount($t + 1, $p->factors);
        dump($p->factors[0]->toString());
        dump($p->factors[1]->toString());
        dump($p->factors[2]->toString());
        dump($p->factors[3]->toString());
        dump($p->factors[4]->toString());
    }

    /**
     * TODO
     * @test
     */
    public function valid_elgamal_parameters()
    {
        $parameterSet = EGParameterSet::default();
        $this->assertTrue($parameterSet->p->isPrime()); // prime
        $this->assertTrue($parameterSet->g->isPrime()); // prime
//        $this->assertTrue($keyPair->pk->q->isPrime());

        list($quotient, $remainder) = $parameterSet->p->subtract(BI1())->divide($parameterSet->g); //

        $this->assertFalse($quotient->equals(BI(0)));
        $this->assertTrue($remainder->equals(BI(0)));
    }

    /**
     * @test
     */
    public function threshold3()
    {

//        $params = DH::createParameters('diffie-hellman-group14-sha1');
//        $params = new CustomDH($params);
//        dump($params->getPrime()->toString());
//        dd($params->getBase()->toString());
//        dd($pk);
//        dd($pk->toBigInteger()->toString());

//        srand(1);

//        $parameterSet = new EGParameterSet(BI(3), BI(13), BI(13));
        $parameterSet = new EGParameterSet(BI(29), BI(59), BI(31));
//        $parameterSet = new EGParameterSet(BI(4), BI(5), BI(5));
//        $parameterSet = EGParameterSet::default();

        dump($parameterSet->toString());

        $t = 1;
        $peer_i = new Peer(1, $parameterSet, $t);
        $peer_j = new Peer(2, $parameterSet, $t);

        $peer_j->setReceivedBroadcast($peer_i->id, $peer_i->getBroadcast());
        $peer_i->setReceivedBroadcast($peer_j->id, $peer_j->getBroadcast());

        $peer_j->setReceivedShare($peer_i->id, $peer_i->getShareToSend($peer_j->id));
        $peer_i->setReceivedShare($peer_j->id, $peer_j->getShareToSend($peer_i->id));

        $this->assertTrue($peer_j->isShareValid($peer_i->id));
        $this->assertTrue($peer_i->isShareValid($peer_j->id));

    }

    /**
     * @test
     */
    public function threshold2()
    {

        srand(1);

        $parameterSet = new EGParameterSet(BI(7), BI(29), BI(29));
//        $parameterSet = new EGParameterSet(BI(29), BI(59), BI(31));

        $t = 2;
        $peers = [
            new Peer(1, $parameterSet, $t),
            new Peer(2, $parameterSet, $t),
            new Peer(3, $parameterSet, $t),
//            new Peer(4, $keyPair, $k),
        ];
        $n = count($peers);

        // broadcast and send shares
        foreach ($peers as $peer_i) {
            $Aik = $peer_i->getBroadcast();

            dump("{$peer_i->id} is broadcasting " . $Aik->toString());

            foreach ($peers as $peer_j) {
                if ($peer_i->id === $peer_j->id) {
                    continue; // not self
                }
                $peer_j->setReceivedBroadcast($peer_i->id, $Aik);

                $share = $peer_i->getShareToSend($peer_j->id);
                dump("{$peer_i->id} is sending share {$share->toString()} to {$peer_j->id}");
                $peer_j->setReceivedShare($peer_i->id, $share);
            }
        }

        // check broadcast and shares
        foreach ($peers as $peer) {
            $this->assertCount($n - 1, $peer->receivedBroadcasts); // not self
            $this->assertCount($n - 1, $peer->shareReceived); // not self
            $this->assertCount($n - 1, $peer->shareSent); // not self
            foreach ($peer->receivedBroadcasts as $broadcast) {
                $this->assertCount($t + 1, $broadcast->values);
            }
        }

        // check shares
        foreach ($peers as $peer_i) {
            foreach ($peers as $peer_j) {
                if ($peer_i->id === $peer_j->id) {
                    continue; // not self
                }
                $this->assertTrue($peer_i->isShareValid($peer_j->id));
            }
        }


        // TODO complaint phase

//        foreach ($peers as $j => $peer_j) {
//            $vk_j = $this->getPublicVerificationKey($shares, $j, $keyPair);
//            dump("VK_$j = " . $vk_j->toString());
//            $peers[$j]['vk'] = $vk_j;
//        }


        /**
         * TODO
         *    foreach ($peers as $j => $peer) {
        $peers[$j]['share'] = $this->combineReceivedSharesIntoShare($peer, $peers, $shares);
        }
        $all = $this->getCombinedSecretKey($peers, $keyPair);
        dump($all->toString());
        array_pop($peers); // remove one peer
        $allButOne = $this->getCombinedSecretKey($peers, $keyPair);
        dump($allButOne->toString());
        // TODO convert vk_j into c_i*
        $this->assertTrue($all->equals($allButOne));
         */

    }

    /**
     * @param $peer
     * @param array $peers
     * @param array $shares
     * @return BigInteger
     */
    private function combineReceivedSharesIntoShare($peer, array $peers, array $shares): BigInteger
    {
        $j = $peer['id'];
        $s = BI1();
        // peer j checks all its shares of all i
        foreach ($peers as $peer_i) {
            $i = $peer_i['id'];
            if ($i == $j) {
                continue; // don't check for self
            }
            $s = $s->add($shares[$i][$j]);
        }
        return $s;
    }

    /**
     * @param array $shares
     * @param int $_j
     * @param EGKeyPair $keyPair
     * @return BigInteger
     */
    private function getPublicVerificationKey(array $shares, int $_j, EGKeyPair $keyPair): BigInteger
    {
        $out = BI1();
        foreach ($shares as $i => $share_array) {
            foreach ($share_array as $j => $share) {
                if ($i !== $_j && $j === $_j) {
                    $out = $out->multiply($share)->modPow(BI1(), $keyPair->pk->parameterSet->p);
                }
            }
        }
        return $out;
    }

    /**
     * @param array $qualifiedPeers
     * @param EGKeyPair $keyPair
     * @return BigInteger
     */
    private function getCombinedSecretKey(array $qualifiedPeers, EGKeyPair $keyPair)
    {
        $s = BI(0);
        foreach ($qualifiedPeers as $peer) {
            /** @var BigInteger $share */
            $share = $peer['share'];
            $lambda = $this->getLagrangianCoefficient(
                array_column($qualifiedPeers, 'id'),
                $peer['id'],
                $keyPair->pk->parameterSet->p
            );
            $s = $s->add(
                $share
                    ->multiply($lambda)
                    ->modPow(BI1(), $keyPair->pk->parameterSet->p)
            );
        }
        return $s;
    }

    /**
     * @param int[] $I
     * @param int $j
     * @param BigInteger $q
     * @return BigInteger
     */
    private function getLagrangianCoefficient(array $I, int $j, BigInteger $q): BigInteger
    {
        $out = BI1();
        foreach ($I as $k) {
            if ($j === $k) {
                continue;
            }
            $out = $out->multiply(BI($k))
                ->multiply(
                    BI($k)->subtract(BI($j))//->modInverse($q)
                );
        }
        return $out->modPow(BI1(), $q);
    }

    /**
     * @test
     */
    public function lagrangian()
    {
        $I = [1, 2, 3, 4]; // no zero
        foreach ($I as $j) {
            dump($this->getLagrangianCoefficient($I, $j, BI(99999))->toString());
        }
        $this->assertTrue(true);
    }


}
