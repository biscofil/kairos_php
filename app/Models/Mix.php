<?php

namespace App\Models;

use App\Jobs\SendP2PMessage;
use App\P2P\Messages\ThisIsMyMixSet\ThisIsMyMixSetRequest;
use App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes;
use App\Voting\CryptoSystems\ElGamal\EGSecretKey;
use App\Voting\CryptoSystems\PublicKey;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webpatser\Uuid\Uuid;

/**
 * Class Mix
 * @package App\Models
 *
 * This class represents a primary mix and all shadow mixes usd for proof
 *
 * @property int id
 * @property int round
 * @property string uuid
 * @property string hash
 * @property bool|null is_valid
 *
 * @property int|null shadow_mix_count
 * @property string|null challenge_bits
 *
 * @property int|null previous_mix_id TODO use uuid / hash
 * @property \App\Models\Mix|null previousMix
 *
 * @property int trustee_id
 * @property \App\Models\Trustee trustee
 *
 * @property int|null mixes_generated_in
 * @property int|null proofs_generated_in
 * @property int|null verified_in
 */
class Mix extends Model
{
    use HasShareableFields;
    use HasFactory;

    protected $table = 'mixes';

    protected $fillable = [
        'round',
        'previous_mix_id', // TODO use uuid / hash
        'uuid',
        'hash',
        'trustee_id',
        'is_valid',
        //
        'shadow_mix_count',
        'challenge_bits',
        //
        'verified_in',
        'proofs_generated_in',
        'mixes_generated_in',
    ];

    public $shareableFields = [
        'round',
        'uuid',
        'hash',
        //
        'shadow_mix_count',
        'challenge_bits',
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
     * @return \App\Voting\AnonymizationMethods\MixNets\Mix
     * @throws \Exception
     */
    public function getInputCipherTextsMix(): \App\Voting\AnonymizationMethods\MixNets\Mix
    {

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode|string $mixNodeClass */
        $mixNodeClass = $this->trustee->election->anonymization_method->getClass();

        /** @var MixWithShadowMixes $mixWithShadowMixesClass */
        $mixClass = $mixNodeClass::getMixClass();

        /** @var MixWithShadowMixes $mixWithShadowMixesClass */
        $mixWithShadowMixesClass = $mixNodeClass::getMixWithShadowMixesClass();

        $election = $this->trustee->election;
        $previousMix = $this->previousMix;

        if (is_null($previousMix)) {
            // first mix, extract ciphertext from bulletin board
            // $this->trustee->peer_server_id === PeerServer::meID &&

            $bulletinBoardFile = $this->getBulletinBoardMixFilename();
            Log::debug('getBulletinBoardMixFilename > ' . $bulletinBoardFile);

            Log::debug('file_exists(Storage::path( .... -> ' . (file_exists(Storage::path($bulletinBoardFile)) ? 'Y' : 'N'));
            Log::debug('Storage::exists( .... -> ' . (Storage::exists($bulletinBoardFile) ? 'Y' : 'N'));

            if (Storage::exists($bulletinBoardFile)) {
                Log::debug('getInputCipherTextsMix > reading generated bulletin board file');
                $inputCipherTextMix = $mixClass::load($this, $bulletinBoardFile);
            } else {
                // i am generating
                Log::debug('getInputCipherTextsMix > generating bulletin board file');
                $inputCipherTextMix = $mixWithShadowMixesClass::extractVotesFromBulletinBoard($election);
                $inputCipherTextMix->store($bulletinBoardFile);
            }
        } else {
            // mix with a previous mix
            Log::debug('getInputCipherTextsMix > using output of previous mix');
            $inputCipherTextMix = $previousMix->getMixWithShadowMixes()->getPrimaryMix();
        }

        Log::debug('Input mix hash : ' . $inputCipherTextMix->getHash());
        Log::debug('Input mix ciphertext count : ' . count($inputCipherTextMix->ciphertexts));

        if ((!is_array($inputCipherTextMix->ciphertexts)) || count($inputCipherTextMix->ciphertexts) === 0) {
            throw new Exception('Mix cipherText array must be non-empty');
        }

        return $inputCipherTextMix;
    }

    /**
     * @param \App\Voting\CryptoSystems\PublicKey|null $publicKey
     * @return \App\Voting\AnonymizationMethods\MixNets\MixWithShadowMixes
     * @throws \Exception
     */
    public function generateMixAndShadowMixes(?PublicKey $publicKey = null): MixWithShadowMixes
    {

        if (is_null($publicKey)) {
            $publicKey = $this->computePublicKeyOfNextTrustees();
        }

        /** @var \App\Voting\AnonymizationMethods\MixNets\MixNode|string $mixClass */
        $mixClass = $this->trustee->election->anonymization_method->getClass();

        /** @var MixWithShadowMixes $mixWithShadowMixesClass */
        $mixWithShadowMixesClass = $mixClass::getMixWithShadowMixesClass();

        if ($this->shadow_mix_count > 160) {
            throw new Exception('The max is 160'); // TODO check, only for elgamal??
        }

        // get the input mix
        $inputMix = $this->getInputCipherTextsMix();

        $nCipherText = count($inputMix->ciphertexts);
        Log::debug('Generating primary mix and shadow mixes on ' . $nCipherText . ' ciphertexts');

        // generate primary mix
        $primaryMixParameterSet = $mixClass::getPrimaryMixParameterSet($publicKey, $nCipherText);
        $primaryMix = $mixClass::forward($inputMix, $primaryMixParameterSet, $this->trustee);
        $primaryMix->store($this->getPrimaryMixFilename());

        // generate shadow mixes
        for ($i = 0; $i < $this->shadow_mix_count; $i++) {

            Log::debug('Generating shadow mix ' . ($i + 1) . ' / ' . $this->shadow_mix_count);

            $shadowMixesParameterSet = $mixClass::getShadowMixParameterSet($publicKey, $nCipherText);
            $shadowMix = $mixClass::forward($inputMix, $shadowMixesParameterSet, $this->trustee);
            $shadowMix->store($this->getShadowMixFilename($i));
        }

        return new $mixWithShadowMixesClass($this);
    }

    // ##########################################

    /**
     * @return string
     */
    public function getMixFolder(): string
    {
        return $this->trustee->election->getElectionFolder() . "mix_{$this->uuid}/";
    }

    /**
     * @return string
     */
    public function getBulletinBoardMixFilename(): string
    {
        return $this->getMixFolder() . 'bb.json';
    }

    /**
     * @return string
     */
    public function getPrimaryMixFilename(): string
    {
        return $this->getMixFolder() . 'primary_mix.json';
    }

    /**
     * @param int $i 0 <= $i < n
     * @return string
     */
    public function getShadowMixFilename(int $i): string
    {
        return $this->getMixFolder() . "shadow_mix_$i.json";
    }

    /**
     * Download from another server
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function download(bool $reDownloadExisting = false): void
    {
        $storageRelativePaths = [
            $this->getPrimaryMixFilename()
        ];

        if (is_null($this->previousMix)) {
            $storageRelativePaths[] = $this->getBulletinBoardMixFilename();
        }

        foreach (range(0, $this->shadow_mix_count - 1) as $idx) {
            $storageRelativePaths[] = $this->getShadowMixFilename($idx);
        }

        $domain = $this->trustee->peerServer->domain;

        $client = new Client();

        foreach ($storageRelativePaths as $storageRelativePath) {

            $absoluteFilePath = Storage::path($storageRelativePath);
            if ((!file_exists($absoluteFilePath)) || $reDownloadExisting) {
                $url = "https://$domain/storage/$storageRelativePath";
                Log::debug("Downloading from $url to $absoluteFilePath");

                $folder = dirname($absoluteFilePath);
                if (!file_exists($folder) && !is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }

                $resource = Utils::tryFopen($absoluteFilePath, 'w');
                $res = $client->request('GET', $url, [
                    'verify' => false, // TODO remove
                    'sink' => $resource
                ]);

                Log::debug('Status code : ' . $res->getStatusCode());
            }
        }

    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDownloadUrlAttribute(): string
    {
        return Storage::url($this->trustee->election->getElectionFolder()); // TODO
    }

    // ##########################################

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

    //    /**
    //     * @return \App\Models\Trustee[]|Collection
    //     */
    //    public function getTrusteeChain(): Collection{
    //        $chainTrustees = [];
    //        $election = $this->trustee->election;
    //        $election->getPeerServerIndexMapping();
    //        $allTrustees = $election->trustees;
    //        return collect($chainTrustees);
    //    }

    /**
     * TODO handle skipped peer servers
     * @return \App\Models\Trustee[]|Collection
     */
    public function getNextTrusteeChain(): Collection
    {
        $election = $this->trustee->election;
        $trustees = [];
        $carry = 0;
        for ($i = $this->round; $i <= ($election->min_peer_count_t + $carry); $i++) { // 1 - t
            $peer = $election->getPeerServerFromIndex($i - 1); // 0 - t-1
            $trustee = $election->getTrusteeFromPeerServer($peer);
//            if ($trustee->isExcluded()) {
//                $carry++;
//                continue;
//            }
            $trustees[] = $trustee;
        }
        return collect($trustees);
    }

    /**
     * @return bool
     */
    public function deleteFiles(): bool
    {
        return Storage::deleteDirectory($this->getMixFolder());
    }

    // ##########################################

    /**
     * @param \App\Models\Election $election
     * @param \App\Models\Mix|null $previousMix
     * @param \App\Models\Trustee|null $trusteeGeneratingMix
     * @return \App\Models\Mix
     * @throws \Exception
     */
    public static function generate(Election $election, ?Mix $previousMix = null, Trustee $trusteeGeneratingMix = null): Mix
    {
        // take the trustee running the mix generation, if null the current server is picked
        if (is_null($trusteeGeneratingMix)) {
            $trusteeGeneratingMix = $election->getTrusteeFromPeerServer(getCurrentServer(), true);
        }

        $mixModel = new static();
        $mixModel->uuid = self::getNewUUID()->string;
        $mixModel->round = is_null($previousMix) ? 1 : ($previousMix->round + 1);
        $mixModel->trustee_id = $trusteeGeneratingMix->id;
        $mixModel->previous_mix_id = $previousMix ? $previousMix->id : null;
        $mixModel->shadow_mix_count = config('kairos.mixnets.shadow_mixes');
        $mixModel->hash = Str::random(100); // will be overwritten
        $mixModel->save();

        Log::debug(' ####################################################################### ');
        Log::debug(" ############### Generating Mix $mixModel->id with UUID $mixModel->uuid");
        Log::debug(' ####################################################################### ');

        // generate shadow mixes
        $publicKey = $mixModel->computePublicKeyOfNextTrustees();
        $start = microtime(true);
        $primaryShadowMixes = $mixModel->generateMixAndShadowMixes($publicKey);
        $mixModel->mixes_generated_in = microtime(true) - $start;
        $mixModel->hash = $primaryShadowMixes->getHash();

        // generate challenge bits & proofs
        $mixModel->setChallengeBits($primaryShadowMixes->getFiatShamirChallengeBits()); // TODO optimize

        // generate proofs and measure time
        $start = microtime(true);
        $primaryShadowMixes->generateProofs($trusteeGeneratingMix);
        $mixModel->proofs_generated_in = microtime(true) - $start;
        $mixModel->save();

        return $mixModel;
    }

    /**
     * @param string $bits
     * @ throws \Exception
     */
    public function setChallengeBits(string $bits): void
    {
//        if (!(str_contains($bits, '1') && str_contains($bits, '0'))) {
//            throw new Exception('The challenge bit string must contain each bit at leats once');
//        }
        $this->challenge_bits = $bits;
        $this->save();
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

        /**
         * @see MixWithShadowMixes::__construct()
         */
        return new $primaryShadowMixesClass($this);

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
            throw new Exception('Calling generateSecretKeyFromShares on an election with t = l .');
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

    // ##########################################

    /**
     * @throws \Exception
     */
    public function verify(): bool
    {

        Log::debug(' ####################################################################### ');
        Log::debug(" ############### Verifying Mix $this->id with UUID $this->uuid generated by peer server " . $this->trustee->peerServer->domain);
        Log::debug(' ####################################################################### ');

        $start = microtime(true);

        $primaryShadowMixes = $this->getMixWithShadowMixes();

        try {
            if ($primaryShadowMixes->isProofValid()) {
                $this->verified_in = microtime(true) - $start;
                $this->setAsValid();
                Log::info('Mix proof is valid!');
                return true;
            }
        } catch (Exception $e) {
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

    /**
     * combine public keys of next trustees
     * @return \App\Voting\CryptoSystems\PublicKey
     */
    public function computePublicKeyOfNextTrustees(): PublicKey
    {
        /** @var PublicKey $publicKey */
        return $this->getNextTrusteeChain()->reduce(function (?PublicKey $carry, Trustee $trustee): PublicKey {
            /** @noinspection PhpParamsInspection */
            return is_null($carry) ? $trustee->public_key : $carry->combine($trustee->public_key);
        });
    }

}
