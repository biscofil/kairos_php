<?php

namespace App\Models;

use App\Jobs\GenerateMix;
use App\Jobs\SendP2PMessage;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class Mix
 * @package App\Models
 * @property int id
 * @property int round
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
        'hash',
        'trustee_id',
        'is_valid',
    ];

    public $shareableFields = [
        'round',
        'hash',
    ];

    protected $casts = [
        'is_valid' => 'bool',
    ];

    protected $appends = [
        'download_url'
    ];

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
        return 'election_' . $this->trustee->election->uuid . '_mix_' . $this->id . '.json';
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

    // ##########################################

    /**
     * @param \App\Models\Election $election
     * @param \App\Models\Mix|null $previousMix
     * @return void
     * @throws \Exception
     */
    public static function generateMix(Election $election, ?Mix $previousMix = null): void
    {

        $cipherTexts = null;
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
        /** @var \App\Voting\CryptoSystems\CipherText[] $cipherTexts */

        Log::debug('Running mix on ' . count($cipherTexts) . ' ciphertexts');

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode|string $mixClass */
        $mixClass = $election->anonymization_method->getClass();

        // generate shadow mixes
        $primaryShadowMixes = $mixClass::generate($election, $cipherTexts, 80);

        // generate challenge bits & proofs
        $primaryShadowMixes->challengeBits = $primaryShadowMixes->getFiatShamirChallengeBits();
        $primaryShadowMixes->generateProofs();

        $meTrustee = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

        $mixModel = new static();
        $mixModel->round = is_null($previousMix) ? 1 : $previousMix->round + 1;
        $mixModel->trustee_id = $meTrustee->id;
        $mixModel->previous_mix_id = $previousMix ? $previousMix->id : null;
        $mixModel->hash = $primaryShadowMixes->getHash();
        $mixModel->save();

        $primaryShadowMixes->store($mixModel->getFilename());

        // send mix to all
        $messagesToSend = $election->peerServers()->get()
            ->map(function (PeerServer $trusteePeerServer) use ($mixModel, $meTrustee) {
                return new ThisIsMyMixSetRequest(
                    getCurrentServer(),
                    $trusteePeerServer,
                    $mixModel
                );
            });

        if ($messagesToSend->count()) {
            SendP2PMessage::dispatch($messagesToSend->toArray());
        }

    }

    /**
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
    private function generateSecretKeyFromShares(Election $election, Trustee $firstTrustee): void
    {

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
     * TODO has to work with both encryption, decryption, re-encryption
     * @throws \Exception
     */
    public function verify(): void
    {
        Log::debug('Running VerifyReceivedMix on MIX # ' . $this->id);

        $primaryShadowMixes = $this->getMixWithShadowMixes();

        try {

            $election = $this->trustee->election;
            $meTrustee = $election->getTrusteeFromPeerServer(getCurrentServer(), true);

            // if fully decrypted, stop
            $completeMixChain = $this->getChainLenght() === $election->min_peer_count_t;

            // TODO check t-l-threshold encryption

            if ($primaryShadowMixes->isProofValid()) {
                $this->setAsValid();
                Log::info('Mix proof is valid!');

                if ($completeMixChain) {

                    Log::info('Chain lenght limit reached');

                    /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode $amClass */
                    $amClass = $election->anonymization_method->getClass();
                    $amClass::afterSuccessfulMixProcess($election);

                    return;
                }

                if ($meTrustee->comesAfterTrustee($this->trustee)) {
                    Log::info('Running GenerateMix from previous mix');

                    // todo filter qualified peer trustees and combine keys
                    $firstValidTrustee = ($this->getMixNodeChain()[0])->trustee;
                    $this->generateSecretKeyFromShares($election, $firstValidTrustee);

                    // if the current peer server is the next in line TODO check
                    GenerateMix::dispatchSync($election, $this);

                    // TODO here we should execute code, not executed because of the same peer issue
                }

            } else {

                $this->setAsInvalid();
                Log::warning('Mix proof is invalid!');

                if ($completeMixChain) {
                    // TODO check
                    Log::info('Chain lenght limit reached');
                    return;
                }

                if ($meTrustee->comesAfterTrustee($this->trustee)) {
                    Log::info('Running GenerateMix from bulletin board');

                    // TODO if t-l-encryption use share of current server and (t-1) keys of the next peers

                    // start from scratch with curent server as first valid mix node
                    $this->generateSecretKeyFromShares($election, $meTrustee);

                    // if the current peer server is the next in line TODO check
                    GenerateMix::dispatchSync($election);
                }

            }
        } catch (\Exception $e) {

            $this->setAsInvalid();
            Log::error('Mix proof failed! > ' . $e->getMessage());
            Log::debug($e);

        }
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


}
