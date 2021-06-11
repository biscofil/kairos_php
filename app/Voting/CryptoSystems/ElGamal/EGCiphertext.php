<?php


namespace App\Voting\CryptoSystems\ElGamal;

use App\Models\CastVote;
use App\Models\Election;
use App\Voting\CryptoSystems\CipherText;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use phpseclib3\Math\BigInteger;
use RuntimeException;

/**
 * Class EGCiphertext
 * @package App\Voting\CryptoSystems\ElGamal;
 * @property EGPublicKey $pk
 * @property BigInteger $alpha
 * @property BigInteger $beta
 */
class EGCiphertext implements CipherText
{

    use BelongsToElgamal;

    public $pk;
    public $alpha;
    public $beta;

    public function __construct(EGPublicKey $pk, BigInteger $alpha, BigInteger $beta)
    {
        $this->pk = $pk;
        $this->alpha = $alpha;
        $this->beta = $beta;
    }

    // ##################################################################################

    /**
     * @param int $userID
     * @param \App\Models\Election $election
     * @param \Illuminate\Http\Request $request
     * @return CastVote[]
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function validateAndStoreVotes(int $userID, Election $election, Request $request): array
    {
        $voteArray = Validator::make($request->all(), [
            'vote' => ['required', 'array'],
            'vote.alpha' => ['required', 'string', 'regex:/^([A-Fa-f0-9]+)$/'], // hex
            'vote.beta' => ['required', 'string', 'regex:/^([A-Fa-f0-9]+)$/'], // hex
        ])->validated();

        $vote = self::fromArray($voteArray['vote'], $election->public_key);

        $cast_vote = new CastVote();
        $cast_vote->vote = $vote;
        $cast_vote->election_id = $election->id;
        $cast_vote->voter_id = $userID; // TODO user ID vs voter ID
        $cast_vote->hash = $vote->getFingerprint();
        $cast_vote->ip = $request->ip();
        $cast_vote->save();

        return [$cast_vote];

    }

    // ##################################################################################

    /**
     * @param array $data
     * @param null|\App\Voting\CryptoSystems\ElGamal\EGPublicKey $publicKey
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return EGCiphertext
     */
    public static function fromArray(array $data, $publicKey = null, bool $ignoreParameterSet = false, int $base = 16): self
    {
        return new static(
            $publicKey ?? static::getCryptosystem()::getPublicKeyClass()::fromArray($data['pk'], $ignoreParameterSet, $base),
            BI($data['alpha'], $base),
            BI($data['beta'], $base)
        );
    }

    /**
     * TODO does not match castable
     * @param bool $includePublicKey
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $includePublicKey = false, bool $ignoreParameterSet = false): array
    {
        $out = [
            'alpha' => $this->alpha->toHex(),
            'beta' => $this->beta->toHex()
        ];
        if ($includePublicKey) {
            $out['pk'] = $this->pk->toArray($ignoreParameterSet);
        }
        return $out;
    }

    // ##################################################################################

    /**
     * TODO check format
     * @return string
     */
    public function getFingerprint(): string
    {
        $v = $this->alpha->toHex() . ',' . $this->beta->toHex();
        return base64_encode(hash('sha256', $v));
    }

    // ################################### Re Encrypt ###################################

    /**
     * @return EGCiphertext
     */
    public function reEncrypt(): EGCiphertext
    {
        /** @var EGCiphertext $ciphertext */
        /** @var BigInteger $r */
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($ciphertext, $r) = $this->reEncryptAndReturnRandomness();
        return $ciphertext;
    }

    /**
     * Encrypt a plaintext and return the randomness just generated and used.
     * @return array
     */
    public function reEncryptAndReturnRandomness(): array
    {
        // r: randomness
        $r = randomBIgt($this->pk->parameterSet->q);
        $ciphertext = $this->reEncryptWithRandomness($r);
        return [$ciphertext, $r];
    }

    /**
     * Re-encrypts the ciphertext
     * @param BigInteger $randomness r
     * @return EGCiphertext
     */
    public function reEncryptWithRandomness(BigInteger $randomness): EGCiphertext
    {

        // a = a * (g ^ r mod p) mod p
        $alpha = $this->alpha
            ->multiply($this->pk->parameterSet->g->modPow($randomness, $this->pk->parameterSet->p))
            ->modPow(BI1(), $this->pk->parameterSet->p);

        // b = b * (y ^ r mod p) mod p
        $beta = $this->beta
            ->multiply($this->pk->y->modPow($randomness, $this->pk->parameterSet->p))
            ->modPow(BI1(), $this->pk->parameterSet->p);

        return new static($this->pk, $alpha, $beta);
    }

    // #################################### Decrypt #####################################

    /**
     * @param BigInteger $randomness
     * @return EGCiphertext
     */
    public function reverseReEncryptionWithRandomness(BigInteger $randomness): self
    {

        // a = a * (g ^ r mod p)^-1
        $alphaOriginal = $this->alpha->multiply(
            $this->pk->parameterSet->g->modPow($randomness, $this->pk->parameterSet->p)->modInverse($this->pk->parameterSet->p)
        )->modPow(BI1(), $this->pk->parameterSet->p);

        // b = b * (y ^ r mod p)^-1
        $betaOriginal = $this->beta->multiply(
            $this->pk->y->modPow($randomness, $this->pk->parameterSet->p)->modInverse($this->pk->parameterSet->p)
        )->modPow(BI1(), $this->pk->parameterSet->p);

        return new static($this->pk, $alphaOriginal, $betaOriginal);

    }

    // ##################################################################################

    /**
     * @param EGCiphertext $b
     * @return bool
     * @throws Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool
    {
        $cpClass = static::getCryptosystem()::getCipherTextClass();

        if (!$b instanceof $cpClass) {
            throw new RuntimeException('EGCiphertext::equals > invalid type, must be EGCiphertext');
        }
        $this->pk->ensureSameParameters($b->pk);
        return $this->alpha->equals($b->alpha) && $this->beta->equals($b->beta);
    }

    // ##################################################################################

    /**
     * When using Decryption-Re-Encryption mixnets, the last stage requires to extract
     * the plaintext from beta
     */
    public function extractPlainTextFromBeta(): EGPlaintext
    {
        $_beta = $this->pk->parameterSet->extractMessageFromSubgroup($this->beta);
        return new EGPlaintext($_beta);
    }

}
