<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGThresholdPolynomial;
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
    public function check_factor_count()
    {
        $parameterSet = new EGParameterSet(BI(10), BI(10), BI(10));

        $t = 4;
        $p = EGThresholdPolynomial::random(BI(1), $t, $parameterSet);
        $this->assertCount($t, $p->factors);
    }

    /**
     * @test
     */
    public function threshold3()
    {

        // G=49, P=311, Q=31
        $parameterSet = new EGParameterSet(
            BI(49),
            BI(311),
            BI(31),
        );
//        $parameterSet = EGParameterSet::default();
        dump($parameterSet->toString());

        $t = 3;
        $peers = [
            new Peer(1, $parameterSet, $t),
            new Peer(2, $parameterSet, $t),
            new Peer(3, $parameterSet, $t),
//            new Peer(4, $keyPair, $k),
        ];
        $n = count($peers);

        // broadcast and send shares
        foreach ($peers as $peer_i) {
            $this->assertValidEGKeyPair($peer_i->pk, $peer_i->sk);
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
            $this->assertCount($n - 1, $peer->receivedShares); // not self
            $this->assertCount($n - 1, $peer->shareSent); // not self
            foreach ($peer->receivedBroadcasts as $broadcast) {
                $this->assertCount($t, $broadcast->A_I_K_values);
            }
        }

        // check shares
        foreach ($peers as $peer_i) {
            foreach ($peers as $peer_j) {
                if ($peer_i->id === $peer_j->id) {
                    continue; // not self
                }
                $this->assertTrue($peer_i->isShareValid($peer_j->id));
                $peer_i->addQualifiedPeer($peer_j->id);
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
     * @test
     */
    public function check_reconstruction()
    {

        // G=49, P=311, Q=31
//        $parameterSet = new EGParameterSet(BI(49), BI(311), BI(31),);
        $parameterSet = EGParameterSet::default();
//        dump($parameterSet->toString());

        $t = rand(4, 8);
        $p = new Peer(rand(1, 100), $parameterSet, $t);

        $peerIDs = range(1, 10);

        for ($_t = 3; $_t < 10; $_t++) {
            // try with a number of peers $_t lower and higher than t

            shuffle($peerIDs);
            $I = array_slice($peerIDs, 0, $_t); // subset

            $k = BI(0);
            foreach ($I as $j) {
                $lambda = getLagrangianCoefficientMod($I, $j, $parameterSet->q);
                $k = $k->add(
                    $p->getShareToSend($j)->multiply($lambda)
                )->modPow(BI1(), $parameterSet->q);
            }

            // if the number of peers $_t is enough (gte t) the value should match
            $this->assertEquals(
                $k->equals($p->sk->x),
                $_t >= $t
            );

        }

    }


}
