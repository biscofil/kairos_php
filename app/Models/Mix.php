<?php

namespace App\Models;

use App\Jobs\SendP2PMessage;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use App\Voting\CryptoSystems\PublicKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webpatser\Uuid\Uuid;

/**
 * Class Mix
 * @package App\Models
 * @property int id
 * @property int round
 * @property string uuid
 * @property string hash
 * @property bool|null is_valid
 *
 * @property int|null previous_mix_id TODO use uuid / hash
 * @property \App\Models\Mix|null previousMix
 *
 * @property int trustee_id
 * @property \App\Models\Trustee trustee
 */
class Mix extends Model
{
    use HasShareableFields;
    use HasFactory;

    protected $fillable = [
        'round',
        'previous_mix_id', // TODO use uuid / hash
        'uuid',
        'hash',
        'trustee_id',
        'is_valid',
    ];

    public $shareableFields = [
        'round',
        'uuid',
        'hash',
    ];

    protected $casts = [
        'is_valid' => 'bool',
    ];

    protected $appends = [
        'download_url'
    ];

    /**
     * @return \Webpatser\Uuid\Uuid
     * @throws \Exception
     */
    public static function getNewUUID(): Uuid
    {
        return Uuid::generate(5, url('mixes/' . (self::count() + 1) . '/' . rand(0, 9999999)), Uuid::NS_URL);
    }

    // ########################################## RELATIONS

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Mix
     * @noinspection PhpUnused
     */
    public function previousMix(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_mix_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\App\Models\Trustee
     * @noinspection PhpUnused
     */
    public function trustee(): BelongsTo
    {
        return $this->belongsTo(Trustee::class, 'trustee_id');
    }

    // ########################################## RELATIONS

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return 'mix_' . $this->id . '.json';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDownloadUrlAttribute(): string
    {
        return Storage::url($this->getFilename());
    }

    /**
     * @return \App\Models\Mix[]
     */
    public function getMixNodeChain(): array
    {
        $mixes = $this->trustee->election->mixes()->get()->keyBy('id');

        $previousID = $this->previous_mix_id;
        $mixChain = [$this];

        while (!is_null($previousID)) {
            /** @var \App\Models\Mix $previousMix */
            $previousMix = $mixes->get($previousID);

            $previousID = $previousMix->previous_mix_id;
            $mixChain[] = $previousMix;
        }

        return array_reverse($mixChain); // [0] => oldest
    }

    /**
     * Returns the lenght >=1 of the mix chain
     * @return int
     */
    public function getChainLenght(): int
    {
        return count($this->getMixNodeChain());
    }

    /**
     * TODO handle skipped peer servers
     * @return \App\Models\Trustee[]|Collection
     */
    public function getNextTrusteeChain(): Collection
    {
        $election = $this->trustee->election;
        $trustees = [];
        for ($i = $this->round; $i <= $election->min_peer_count_t; $i++) {
            $peer = $election->getPeerServerFromIndex($i - 1);
            $trustees[] = $election->getTrusteeFromPeerServer($peer);
        }
        return collect($trustees);
    }

    // ##########################################

    /**
     * @param \App\Models\Election $election
     * @param \App\Models\Mix|null $previousMix
     * @return \App\Voting\CryptoSystems\CipherText[]
     * @throws \Exception
     */
    private static function getCipherTexts(Election $election, ?Mix $previousMix): array
    {
        // get ciphertexts
        if (is_null($previousMix)) {
            // first mix, extract ciphertext from bulletin board

            /** @var Collection|\App\Voting\CryptoSystems\CipherText[] $cipherTexts */
            $cipherTexts = $election->votes()->onlyLastOfVoters()->get()->map(function (CastVote $castVote) {
                return $castVote->vote;
            })->toArray();
        } else {
            // mix with a previous mix
            $cipherTexts = $previousMix->getMixWithShadowMixes()->primaryMix->ciphertexts;
        }

        if ((!is_array($cipherTexts)) || count($cipherTexts) === 0) {
            throw new \Exception('cipherTexts must be a non-empty array');
        }

        return $cipherTexts;
    }

    /**
     * @param \App\Models\Election $election
     * @param \App\Models\Mix|null $previousMix
     * @param \App\Models\Trustee|null $trusteeGeneratingMix
     * @return \App\Models\Mix
     * @throws \Exception
     */
    public static function generate(Election $election, ?Mix $previousMix = null, Trustee $trusteeGeneratingMix = null): Mix
    {
        if (is_null($trusteeGeneratingMix)) {
            $trusteeGeneratingMix = $election->getTrusteeFromPeerServer(getCurrentServer(), true);
        }

        $mixModel = new static();
        $mixModel->uuid = self::getNewUUID()->string;
        $mixModel->round = is_null($previousMix) ? 1 : $previousMix->round + 1;
        $mixModel->trustee_id = $trusteeGeneratingMix->id;
        $mixModel->previous_mix_id = $previousMix ? $previousMix->id : null;

        // combine public keys of next trustees
        $publicKey = $mixModel->getNextTrusteeChain()->reduce(function (?PublicKey $carry, Trustee $trustee) {
            if (is_null($carry)) {
                //first
                return $trustee->public_key;
            }
            /** @noinspection PhpParamsInspection */
            return $carry->combine($trustee->public_key);
        }, null);

        // TODO generate $publicKey based on next peers

        $cipherTexts = self::getCipherTexts($election, $previousMix);

        Log::debug('Running mix on ' . count($cipherTexts) . ' ciphertexts');

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode|string $mixClass */
        $mixClass = $election->anonymization_method->getClass();

        // generate shadow mixes
        $primaryShadowMixes = $mixClass::generateMixAndShadowMixes($election,
            $cipherTexts, $trusteeGeneratingMix,
            $publicKey, config('kairos.mixnets.shadow_mixes'));

        // generate challenge bits & proofs
        $primaryShadowMixes->setChallengeBits($primaryShadowMixes->getFiatShamirChallengeBits());
        $primaryShadowMixes->generateProofs($trusteeGeneratingMix);

        $mixModel->hash = $primaryShadowMixes->getHash();
        $mixModel->save();

        $primaryShadowMixes->store($mixModel->getFilename());

        return $mixModel;
    }

    /**
     * Loads from file
     * @return \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     * @throws \Exception
     */
    public function getMixWithShadowMixes(): MixWithShadowMixes
    {

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $amClass */
        $amClass = $this->trustee->election->anonymization_method->getClass();

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes $primaryShadowMixesClass */
        $primaryShadowMixesClass = $amClass::getMixWithShadowMixesClass();

        return $primaryShadowMixesClass::load($this->getFilename());

    }

    /**
     * Sets the secret key of the current trustee as the sum of shares received
     * @param \App\Models\Election $election
     * @param \App\Models\Trustee $firstTrustee
     * @throws \Exception
     */
    public function generateSecretKeyFromShares(Election $election, Trustee $firstTrustee): void
    {

        if ($election->hasLLThresholdScheme()) {
            throw new \Exception('Calling generateSecretKeyFromShares on an election with t = l .');
        }

        $meTrustee = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

        $index = $firstTrustee->getPeerServerIndex();
        $receivedShares = [];
        for ($i = 0; $i < $election->min_peer_count_t; $i++) {
            $peer = $election->getPeerServerFromIndex($index);
            $trustee = $election->getTrusteeFromPeerServer($peer);
            $receivedShares[$index] = $trustee->share_received;
            $index = $election->getIndexAfter($index);
        }

        $sk = EGSecretKey::fromThresholdShares($election->public_key, $receivedShares);// TODO generalize

        $meTrustee->private_key = $sk;
        $meTrustee->save();
    }

    /**
     * @throws \Exception
     */
    public function verify(): bool
    {
        Log::debug('Running VerifyReceivedMix on MIX # ' . $this->id . ' generated by peer server ' . $this->trustee->peerServer->domain);

        $primaryShadowMixes = $this->getMixWithShadowMixes();

        try {
            if ($primaryShadowMixes->isProofValid($this->trustee)) {
                $this->setAsValid();
                Log::info('Mix proof is valid!');
                return true;
            }
        } catch (\Exception $e) {
            $this->setAsInvalid();
            Log::error('Mix proof failed! > ' . $e->getMessage());
            Log::debug($e);
        }

        $this->setAsInvalid();
        Log::warning('Mix proof is invalid!');
        return false;
    }

    /**
     * @return void
     */
    public function setAsValid(): void
    {
        $this->is_valid = true;
        $this->save();
    }

    /**
     * @return void
     */
    public function setAsInvalid(): void
    {
        $this->is_valid = false;
        $this->save();
        // TODO dispatch
    }

    // ##########################################

    /**
     *
     */
    public function afterGeneration(): void
    {

        $election = $this->trustee->election;

        // send mix to all
        // TODO prioritize next in chain
        $messagesToSend = $election->peerServers()->get()
            ->map(function (PeerServer $trusteePeerServer) {
                return new ThisIsMyMixSetRequest(
                    getCurrentServer(),
                    $trusteePeerServer,
                    $this
                );
            });

        if ($messagesToSend->count()) {
            SendP2PMessage::dispatch($messagesToSend->toArray());
        }
    }

}
