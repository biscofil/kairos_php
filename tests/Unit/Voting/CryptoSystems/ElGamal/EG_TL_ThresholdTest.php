<?php


namespace Tests\Unit\Voting\CryptoSystems\ElGamal;


use App\Voting\CryptoSystems\ElGamal\EGKeyPair;
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

        $keyPair = EGKeyPair::generate();
        $keyPair->pk->p = BI(10);
        $keyPair->pk->q = BI(10);

        $t = 4;
        $p = EGThresholdPolynomial::random($t, $keyPair->pk);
        $this->assertCount($t + 1, $p->factors);
        dump($p->factors[0]->toString());
        dump($p->factors[1]->toString());
        dump($p->factors[2]->toString());
        dump($p->factors[3]->toString());
        dump($p->factors[4]->toString());
    }

    /**
     * @test
     */
    public function compute()
    {

        $keyPair = EGKeyPair::generate();
        $keyPair->pk->p = BI(100);
        $keyPair->pk->q = BI(100);

        $p = new EGThresholdPolynomial(
            [BI(2), BI(3), BI(4), BI(5)],
            $keyPair->pk
        );

        $this->assertCount(4, $p->factors);
        $this->assertTrue($p->compute(BI(0))->equals($p->factors[0]));
        $this->assertTrue($p->compute(BI(3))->equals(BI(82)));
    }

    /**
     * TODO
     * @test
     */
    public function valid_elgamal_parameters()
    {
        $keyPair = EGKeyPair::generate();

        $this->assertTrue($keyPair->pk->p->isPrime());
        $this->assertTrue($keyPair->pk->q->isPrime());

        list($quotient, $remainder) = $keyPair->pk->q->divide($keyPair->pk->p->subtract(BI1()));
        dump($quotient->toString());
        dump($remainder->toString());
        $this->assertTrue($remainder->equals(BI(0)));
    }

    /**
     * @test
     */
    public function threshold()
    {

        $peers = array_fill_keys(range(1, rand(5, 6)), []);

        $n = count($peers);
        $t = rand(3, $n - 1);

        dump("N = $n , T = $t");

        $broadcast = [];
        $shares = [];

        $keyPair = EGKeyPair::generate();

        // only works with this
        $keyPair->pk->p = BI(100000);
        $keyPair->pk->q = BI(100000);
//        $keyPair->pk->q = $keyPair->pk->p;

        $keyPair->sk->x = randomBIgt($keyPair->pk->q);
        $keyPair->pk->y = $keyPair->pk->g->modPow($keyPair->sk->x, $keyPair->pk->p); // also called h

        foreach ($peers as $i => $peer_i) {

            $peers[$i]['id'] = $i;

            $f_i = $keyPair->generatePolynomial($t);
            $peers[$i]['f_i'] = $f_i;

//            $f_i_prime = $keyPair->generatePolynomial($t);
//            $peers[$i]['f_i_prime'] = $f_i_prime;

            dump($f_i->toString() /*. " #### " . $f_i_prime->toString()*/);

            $this->assertCount($t + 1, $f_i->factors);
            $secret = $f_i->compute(BI(0));
            $peers[$i]['secret'] = $secret;

            // broadcast
            $broadcast[$i] = $f_i->getBroadcast();
            $this->assertCount($t + 1, $broadcast[$i]->values);

            // compute shares
            $shares[$i] = []; // from > to > share
            foreach ($peers as $j => $peer_j) {
                if ($j == $i) {
                    continue; // skip self
                }
                $share = $f_i->compute(BI($j));
                $shares[$i][$j] = $share;
            }
            $this->assertCount($n - 1, $shares[$i]);

        }

        $this->assertCount($n, $broadcast);
        $this->assertCount($n, $shares);

        // each peer j performs a check
        foreach ($peers as $j => $peer_j) {
            // peer j checks all its shares of all i
            foreach ($peers as $i => $peer_i) {
                if ($i == $j) {
                    continue; // don't check for self
                }
                $this->assertTrue($broadcast[$i]->isValid($shares[$i][$j], $j));
            }
        }


        // TODO complaint phase

//        foreach ($peers as $j => $peer_j) {
//            $vk_j = $this->getPublicVerificationKey($shares, $j, $keyPair);
//            dump("VK_$j = " . $vk_j->toString());
//            $peers[$j]['vk'] = $vk_j;
//        }

        foreach ($peers as $j => $peer) {
            $peers[$j]['share'] = $this->combineReceivedSharesIntoShare($peer, $peers, $shares);
        }

        $all = $this->getCombinedSecretKey($peers, $keyPair);
        dump($all->toString());

        array_pop($peers); // remove one peer

        $allButOne = $this->getCombinedSecretKey($peers, $keyPair);
        dump($allButOne->toString());

        // TODO convert vk_j into c_i*

        $this->assertTrue($all->equals($allButOne));

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
                    $out = $out->multiply($share)->modPow(BI1(), $keyPair->pk->p);
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
                $keyPair->pk->p
            );
            $s = $s->add(
                $share
                    ->multiply($lambda)
                    ->modPow(BI1(), $keyPair->pk->p)
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
