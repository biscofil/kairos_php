<?php


namespace App\Crypto\MixNets;


use App\Crypto\EGCiphertext;
use phpseclib3\Math\BigInteger;

/**
 * Class MixNet
 * @package App\Crypto\MixNets
 * @property Mix primaryMix
 * @property Mix[] shadowMixes
 * @property int shadowMixCount
 * @property EGCiphertext[] originalCiphertexts
 */
class MixNode
{

    public Mix $primaryMix;
    public array $shadowMixes;
    public int $shadowMixCount;
    public array $originalCiphertexts;

    /**
     * MixNet constructor.
     * @param EGCiphertext[] $ciphertexts
     */
    public function __construct(array $ciphertexts)
    {
        $this->originalCiphertexts = $ciphertexts;
    }

    /**
     * @param int $shadowMixCount
     * @throws \Exception
     */
    public function generate(int $shadowMixCount = 100)
    {
        if ($shadowMixCount > 160) {
            throw new \Exception("The max is 160");
        }

//        $start = microtime(true);
        $this->primaryMix = new Mix($this->originalCiphertexts); // TODO do not export parameters of this

        $this->shadowMixCount = $shadowMixCount;
//        dump("shadowMixes = $shadowMixCount");
        $this->shadowMixes = [];
        for ($i = 0; $i < $shadowMixCount; $i++) {
            $this->shadowMixes[] = new Mix($this->originalCiphertexts);
        }

//        $time_elapsed_secs = microtime(true) - $start;
//        dump($time_elapsed_secs / (count($this->originalCiphertexts) * ($this->shadowMixCount + 1)) . " secs/shadowCopy/cipherText");
    }

    /**
     * @return string
     */
    public function generateFiatShamirChallengeBits(): string
    {
        $hex = sha1(implode("", array_map(function (Mix $mix) {
            return $mix->getHash();
        }, $this->shadowMixes)));
        $fullLen = (new BigInteger($hex, 16))->toBits();
        return substr($fullLen, 0, $this->shadowMixCount);
    }

    /**
     * @param string $bits
     * @return MixNodeParameterSet[]
     */
    public function generateProofs(string $bits): array
    {
        $out = [];
        for ($i = 0; $i < strlen($bits); $i++) {
            $bit = $bits[$i];
            $mix = $this->shadowMixes[$i];
            if ($bit === "0") {
                $out[] = $mix->parameterSet;
            } else {
                $out[] = $mix->parameterSet->combine($this->primaryMix->parameterSet);
            }
        }
        return $out;
    }

    /**
     * @return EGCiphertext[]
     */
    public function toArray(): array
    {
        return $this->originalCiphertexts; // TODO check
    }

    public function checkProofs(array $mixParam)
    {

    }

    public function store()
    {
        // TODO ignore params of primary mix
        // TODO save to mysql, json or whatever
    }

    public static function load(): MixNode
    {
        // TODO load from mysql, json or whatever
    }

}
