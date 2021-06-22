<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGParameterSet;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
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
        static::assertCount($t, $p->factors);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function threshold()
    {

        // G=49, P=311, Q=31
//        $parameterSet = new EGParameterSet(
//            BI(49),
//            BI(311),
//            BI(31),
//        );
        $parameterSet = EGParameterSet::getDefault();

        $t = 3;
        $peers = [
            new Peer(1, $parameterSet, $t),
            new Peer(2, $parameterSet, $t),
            new Peer(3, $parameterSet, $t),
            new Peer(4, $parameterSet, $t),
//            new Peer(5, $parameterSet, $t),
        ];
        $n = count($peers);

        // broadcast and send shares
        foreach ($peers as $peer_i) {
            self::assertValidEGKeyPair($peer_i->pk, $peer_i->sk);
            $Aik = $peer_i->getBroadcast();

            foreach ($peers as $peer_j) {
                if ($peer_i->id === $peer_j->id) {
                    continue; // not self
                }
                $peer_j->setReceivedBroadcast($peer_i->id, $Aik);
                $peer_j->setReceivedShare($peer_i->id, $peer_i->getShareToSend($peer_j->id));
            }
        }

        // check broadcast and shares
        foreach ($peers as $peer) {
            static::assertCount($n - 1, $peer->receivedBroadcasts); // not self
            static::assertCount($n - 1, $peer->receivedShares); // not self
            static::assertCount($n - 1, $peer->shareSent); // not self
            foreach ($peer->receivedBroadcasts as $broadcast) {
                static::assertCount($t, $broadcast->A_I_K_values);
            }
        }

        // check shares
        foreach ($peers as $peer_i) {
            foreach ($peers as $peer_j) {
                if ($peer_i->id === $peer_j->id) {
                    continue; // not self
                }
                static::assertTrue($peer_i->isShareValid($peer_j->id));
                $peer_i->addQualifiedPeer($peer_j->id);
            }
        }


        // ########################## public key --> all share the same
        $pk = [];
        foreach ($peers as $peer) {
            $y = $peer->getCombinedPublicKey();
            $pk[] = $y;
            if (count($pk)) {
                static::assertTrue($pk[0]->equals($y));
            }
        }
        $pk = $pk[0];


        // ########################## true (virtual) private key
        $virtual_secret_x = null;
        foreach ($peers as $peer) {
            $virtual_secret_x = $peer->sk->combine($virtual_secret_x);
        }
        $virtual_secret_x->x = mod($virtual_secret_x->x, $parameterSet->q);
        self::assertValidEGKeyPair($pk, $virtual_secret_x);


        // ########################## share private key
        foreach ($peers as $peer) {
            $peer->skShare = $peer->computeX_j($pk); // true
        }

        $I = $peers; // shared
        shuffle($I);
        $I = array_slice($I, 0, $t); // subset
        /** @var \Tests\Unit\Voting\CryptoSystems\ElGamal\Peer[] $I */
        $I = array_values($I);

        $I_IDS = array_column($I, 'id');

        $share_secret_x = null;
        foreach ($I as $peer) {

            $lambda = getLagrangianCoefficientMod($I_IDS, $peer->id, $parameterSet->q);

            $x_j = $peer->skShare;
            $x_j->x = mod($x_j->x->multiply($lambda), $parameterSet->q);

            $share_secret_x = $x_j->combine($share_secret_x);

        }
        $share_secret_x->x = mod($share_secret_x->x, $parameterSet->q);
        static::assertTrue($share_secret_x->x->equals($virtual_secret_x->x));

    }

    /**
     * @test
     */
    public function check_reconstruction()
    {

        // G=49, P=311, Q=31
//        $parameterSet = new EGParameterSet(BI(49), BI(311), BI(31),);
        $parameterSet = EGParameterSet::getDefault();

        $t = rand(4, 8);
        $p = new Peer(rand(1, 100), $parameterSet, $t);

        $peerIDs = range(1, 10);

        for ($_t = 3; $_t < 10; $_t++) {
            // try with a number of peers $_t lower and higher than t

            shuffle($peerIDs);
            $I = array_slice($peerIDs, 0, $_t); // subset

            // retrieve shares
            $receivedShares = [];
            foreach ($I as $j) {
                $receivedShares[$j] = $p->getShareToSend($j);
            }

            $k = EGSecretKey::fromThresholdShares(
                $p->pk,
                $receivedShares
            );

            // if the number of peers $_t is enough (gte t) the value should match
            static::assertEquals(
                $k->equals($p->sk),
                $_t >= $t
            );

        }

    }


}
