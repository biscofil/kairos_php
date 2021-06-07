<?php


namespace App\Voting\CryptoSystems\RSA;

use App\Models\CastVote;
use App\Models\Election;
use App\Voting\CryptoSystems\CipherText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class RSACiphertext
 * @package App\Voting\CryptoSystems\RSA;
 * @property RSAPublicKey $pk
 * @property string $cipherText
 */
class RSACiphertext implements CipherText
{

    use BelongsToRSA;

    public RSAPublicKey $pk;
    public string $cipherText;

    public function __construct(RSAPublicKey $pk, string $cipherText)
    {
        $this->pk = $pk;
        $this->cipherText = $cipherText;
    }

    // ##################################################################################
    // ##################################################################################
    // ##################################################################################

    /**
     * @param array $data
     * @param RSAPublicKey|null $publicKey
     * @param bool $ignoreParameterSet
     * @param int $base
     * @return RSACiphertext
     */
    public static function fromArray(array $data, $publicKey = null, bool $ignoreParameterSet = false, int $base = 16): RSACiphertext
    {
        return new static(
            $publicKey ?? RSAPublicKey::fromArray($data['pk'], $ignoreParameterSet, $base),
            base64_decode($data['c'])
        );
    }

    /**
     * @param bool $includePublicKey
     * @param bool $ignoreParameterSet
     * @return array
     */
    public function toArray(bool $includePublicKey = false, bool $ignoreParameterSet = false): array
    {
        $out = [
            'c' => base64_encode($this->cipherText)
        ];
        if ($includePublicKey) {
            $out['pk'] = $this->pk->toArray($ignoreParameterSet);
        }
        return $out;
    }

    // ##################################################################################
    // ##################################################################################
    // ##################################################################################

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        $v = $this->cipherText;
        return base64_encode(hash('sha256', $v));
    }

    /**
     * @param RSACiphertext $b
     * @return bool
     * @throws \Exception
     * @noinspection PhpMissingParamTypeInspection
     */
    public function equals($b): bool
    {
        if (!$b instanceof RSACiphertext) {
            throw new \RuntimeException('RSACiphertext::equals > invalid type, must be RSACiphertext');
        }
        // TODO $this->pk->ensureSameCryptosystem($b->pk);
        return $this->cipherText === $b->cipherText;
    }

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
            'vote.c' => ['required', 'string'],
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

}
