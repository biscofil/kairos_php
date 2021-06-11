<?php


namespace App\Voting\AnonymizationMethods\MixNets\ReEncryption;


use App\Jobs\SendP2PMessage;
use App\Models\Election;
use App\Models\Trustee;
use App\P2P\Messages\ThisIsMySecretKey\ThisIsMySecretKeyRequest;
use App\Voting\AnonymizationMethods\MixNets\Mix;
use App\Voting\AnonymizationMethods\MixNets\MixNode;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\SecretKey;
use Illuminate\Support\Facades\Log;

/**
 * Class ReEncryptingMixNode
 * @package App\Voting\AnonymizationMethods\MixNets
 */
class ReEncryptingMixNode extends MixNode
{

    /**
     * @param Election $election
     * @param array $ciphertexts
     * @param \App\Voting\AnonymizationMethods\MixNets\ReEncryption\ReEncryptionParameterSet|null $parameterSet
     * @return Mix
     * @throws \Exception
     */
    public static function forward(Election $election, array $ciphertexts, $parameterSet = null): Mix
    {

        if (is_null($parameterSet)) {
            // if not provided, generate as many randomness factors as there are ciphertexts
            $parameterSet = ReEncryptionParameterSet::create($election->public_key, count($ciphertexts));
        }

        // re-encrypt
        $reEncryptedCiphertexts = [];
        foreach ($ciphertexts as $idx => $ciphertext) {
            $r = $parameterSet->reEncryptionFactors[$idx];
            /** @var \App\Voting\CryptoSystems\ElGamal\EGCiphertext $ciphertext */
            $reEncryptedCiphertexts[] = $ciphertext->reEncryptWithRandomness($r); // TODO generalize
        }

        // shuffle
        $reEncryptedCiphertexts = $parameterSet->permuteArray($reEncryptedCiphertexts);

        return new ReEncryptionMix(
            $election,
            $reEncryptedCiphertexts,
            $parameterSet
        );

    }

    /**
     * @return string|ReEncryptionMixWithShadowMixes
     */
    public static function getMixWithShadowMixesClass(): string
    {
        return ReEncryptionMixWithShadowMixes::class;
    }

    /**
     * @return string|ReEncryptionMix
     */
    public static function getMixClass(): string
    {
        return ReEncryptionMix::class;
    }

    /**
     * @return string|ReEncryptionParameterSet
     */
    public static function getParameterSetClass(): string
    {
        return ReEncryptionParameterSet::class;
    }

    /**
     * @param \App\Models\Election $election
     * @throws \Exception
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public static function afterSuccessfulMixProcess(Election &$election): void
    {

        Log::debug('ReEncryptingMixNode afterSuccessfulMixProcess > dispatching ThisIsMySecretKeyRequest');

        // send secret key to the coordinator
        SendP2PMessage::dispatch(
            new ThisIsMySecretKeyRequest(
                getCurrentServer(),
                $election->peerServerAuthor,
                $election,
                $election->getTrusteeFromPeerServer(getCurrentServer())->private_key
            )
        );

    }

    /**
     * @param \App\Models\Election $election
     * @param \App\Models\Trustee $trustee
     * @throws \Exception
     */
    public static function onSecretKeyReceived(Election $election, Trustee $trustee)
    {
        // check if all secret keys have been received

        /** @var \App\Models\Mix $lastMix */
        $lastMix = $trustee->election->mixes()->latest()->firstOrFail(); // TODO check!!!
        $mixChain = $lastMix->getMixNodeChain();

        $mixChainTrusteeIDs = array_map(function (\App\Models\Mix $mix) {
            return $mix->trustee_id;
        }, $mixChain);

        // count how many needed trustees have yet to send secret keys
        $missingSecretKeyCount = $election->trustees()
            ->whereIn('trustees.id', $mixChainTrusteeIDs)
            ->whereNull('private_key')
            ->count();

        if ($missingSecretKeyCount === 0) {
            self::onAllSecretKeysReceived($election, $mixChainTrusteeIDs);
        }

    }

    /**
     * This is executed by the bulletin board once all secret keys have been shared by the trustees after the mix procedure
     * @param \App\Models\Election $election
     * @param array $mixChainTrusteeIDs
     * @throws \Exception
     */
    public static function onAllSecretKeysReceived(Election $election, array $mixChainTrusteeIDs)
    {

        self::combineTrusteePrivateKeys($election, $mixChainTrusteeIDs);

        $plainTexts = self::decryptVotes($election);

        self::storePlainTextBallots($election, $plainTexts);

        self::runTally($election);

    }

    /**
     * @param \App\Models\Election $election
     * @return \App\Voting\CryptoSystems\Plaintext[]
     * @throws \Exception
     */
    public static function decryptVotes(Election &$election): array
    {

        Log::debug('Decrypting votes...');

        /** @var \App\Models\Mix $lastMix */
        $lastMix = $election->mixes()->latest()->firstOrFail();

        $cipherTexts = $lastMix->getMixWithShadowMixes()->primaryMix->ciphertexts;

        return array_map(function (CipherText $cipherText) use ($election) {
            return $election->private_key->decrypt($cipherText);
        }, $cipherTexts);

    }

    /**
     * @param \App\Models\Election $election
     * @param array $mixChainTrusteeIDs
     * @return bool
     */
    private static function combineTrusteePrivateKeys(Election &$election, array $mixChainTrusteeIDs): bool
    {
        Log::debug('Setting private key of the election...');
        // if this is the last one, trigger mixnet decryption

        $neededTrustees = $election->trustees()->whereIn('trustees.id', $mixChainTrusteeIDs)->get();

        $election->private_key = $neededTrustees->reduce(function (?SecretKey $carry, Trustee $trustee) {
            if (is_null($carry)) {
                return $trustee->private_key;
            }
            return $trustee->private_key->combine($carry); // TODO check polymorphism
        });

        return $election->save();
    }


//    private static function

}
