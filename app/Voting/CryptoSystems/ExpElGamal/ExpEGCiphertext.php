<?php


namespace App\Voting\CryptoSystems\ExpElGamal;


use App\Voting\CryptoSystems\ElGamal\EGCiphertext;

/**
 * Class ExpEGCiphertext
 * @package App\Voting\CryptoSystems\ExpElGamal
 */
class ExpEGCiphertext extends EGCiphertext
{

    use BelongsToExpElgamal;

    /**
     * @param \App\Voting\CryptoSystems\ExpElGamal\ExpEGCiphertext $b
     * @return self
     */
    public function homomorphicSum(ExpEGCiphertext $b): self
    {
        return new static(
            $this->pk,
            $this->alpha->multiply($b->alpha)->modPow(BI1(), $this->pk->parameterSet->p),
            $this->beta->multiply($b->beta)->modPow(BI1(), $this->pk->parameterSet->p)
        );
    }

}
