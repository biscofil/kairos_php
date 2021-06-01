<?php

namespace App\Models;

use App\Jobs\GenerateMix;
use App\Jobs\SendP2PMessage;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
        'previous_mix_id',
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
        return 'election_' . $this->trustee->election->uuid . '_mix_' . $this->id;
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
            $cipherTexts = $election->votes()->get()->map(function (CastVote $castVote) {
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

        $meTrustee = $election->getTrusteeFromPeerServer(PeerServer::me(), true);

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
                    PeerServer::me(),
                    $trusteePeerServer,
                    $mixModel
                );
            });

        if ($messagesToSend->count()) {
            SendP2PMessage::dispatch($messagesToSend->toArray());
        }

    }

    // ##########################################

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

    // ##########################################

    /**
     * @throws \Exception
     */
    public function verify(): void
    {
        Log::debug('Running VerifyReceivedMix on MIX # ' . $this->id);

        $primaryShadowMixes = $this->getMixWithShadowMixes();

        try {

            $election = $this->trustee->election;
            $meTrustee = $election->getTrusteeFromPeerServer(PeerServer::me(), true);

            // TODO if fully decrypted, stop
            // TODO check t-l-threshold encryption

            if ($primaryShadowMixes->isProofValid()) {
                $this->setAsValid();
                Log::info('Mix proof is valid!');

                if ($meTrustee->comesAfterTrustee($this->trustee)) {
                    Log::info('Running GenerateMix from previous mix');

                    // if the current peer server is the next in line TODO check
                    GenerateMix::dispatchSync($election, $this);
                }

            } else {

                $this->setAsInvalid();
                Log::warning('Mix proof is invalid!');

                if ($meTrustee->comesAfterTrustee($this->trustee)) {
                    Log::info('Running GenerateMix from bulletin board');

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
