<?php


namespace App\Voting\AnonymizationMethods\MixNets;


use App\Jobs\GenerateMix;
use App\Models\Election;
use App\Voting\AnonymizationMethods\AnonymizationMethod;
use App\Voting\CryptoSystems\CipherText;
use App\Voting\CryptoSystems\PublicKey;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use SQLite3;

/**
 * Class MixNet
 * @package App\Voting\MixNets
 * @property PublicKey $pk
 * @property Mix primaryMix
 * @property Mix[] shadowMixes
 * @property int shadowMixCount
 * @property Ciphertext[] originalCiphertexts
 * @property string $challengeBits
 */
abstract class MixNode implements AnonymizationMethod
{

    public PublicKey $pk;
    public Mix $primaryMix;
    public array $shadowMixes;
    public int $shadowMixCount;
    public array $originalCiphertexts;
    public string $challengeBits;

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param int $shadowMixCount
     * @throws \Exception
     */
    public function generate(PublicKey $pk, array $ciphertexts, int $shadowMixCount = 100)
    {
        $this->pk = $pk;

        if ($shadowMixCount > 160) {
            throw new \Exception('The max is 160'); // TODO only for elgamal
        }
        $this->originalCiphertexts = $ciphertexts;

        $this->primaryMix = static::forward($this->pk, $this->originalCiphertexts);

        $this->shadowMixCount = $shadowMixCount;
        $this->shadowMixes = [];
        for ($i = 0; $i < $shadowMixCount; $i++) {
            $this->shadowMixes[] = static::forward($this->pk, $this->originalCiphertexts);
        }
    }

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     */
    abstract public static function forward(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null): Mix;

    /**
     * @param PublicKey $pk
     * @param array $ciphertexts
     * @param MixNodeParameterSet|null $parameterSet
     * @return Mix
     */
    abstract public static function backward(PublicKey $pk, array $ciphertexts, MixNodeParameterSet $parameterSet = null): Mix;

    // ########################################################################

    /**
     * @return void
     */
    public function generateFiatShamirChallengeBits(): void
    {
        $hex = sha1(implode('', array_map(function (Mix $mix) {
            return $mix->getHash();
        }, $this->shadowMixes)));
        $fullLen = (BI($hex, 16))->toBits();
        $this->challengeBits = substr($fullLen, 0, $this->shadowMixCount);
    }

    /**
     * @return MixNodeParameterSet[]
     * @throws \Exception
     */
    public function generateProofs(): array
    {
        $out = [];
        for ($i = 0; $i < strlen($this->challengeBits); $i++) {
            $bit = $this->challengeBits[$i];
            $mix = $this->shadowMixes[$i];
            if ($bit === '0') {
                $out[] = $mix->parameterSet;
            } else {
                $out[] = $mix->parameterSet->combine($this->primaryMix->parameterSet);
            }
        }
        return $out;
    }

    /**
     * @param MixNodeParameterSet[] $parameterSets
     * @param string $bits
     * @return bool
     * @throws \Exception
     */
    public function isProofValid(array $parameterSets, string $bits): bool
    {
        // TODO
        return true;
    }

    // ########################################################################

    /**
     * @param string $fileName
     * @param bool $storePrivateValues
     * @return mixed
     */
    public function store(string $fileName, bool $storePrivateValues = false): void
    {
        $jsonFilePath = $fileName . '.json';
        $data = $this->toArray($storePrivateValues);
        Storage::put($jsonFilePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * TODO check
     * @param string $fileName
     */
    public function toSqlite(string $fileName)
    {

        $filePath = base_path($fileName . '.sqlite');
        $name = 'temp';
        Config::set('database.connections.' . $name, [
            'driver' => 'sqlite',
            'database' => $filePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (!file_exists($filePath)) {
            new SQLite3($filePath);

            Schema::connection($name)->create('shadow_mixes', function (Blueprint $table) {
                $table->id();
                $table->string('beta');
                $table->timestamps();
            });

            foreach ($this->shadowMixes as $idx => $shadowMix) {
                Schema::connection($name)->create('shadow_mix_' . $idx, function (Blueprint $table) {
                    $table->id();
                    $table->string('alpha');
                    $table->string('beta');
                    $table->timestamps();
                });
            }
        }

        foreach ($this->shadowMixes as $idx => $shadowMix) {
            DB::connection($name)->table('shadow_mix_' . $idx)
                ->insert(array_map(function (Ciphertext $cipherText) {
                    return [
                        'alpha' => $cipherText->cipherText->toHex(),
                        'beta' => $cipherText->beta->toHex()
                    ];
                }, $shadowMix->ciphertexts));
        }
    }

    /**
     * @param string $fileName
     * @return MixNode
     * @throws \Exception
     */
    public static function load(string $fileName): MixNode
    {
        $jsonFilePath = $fileName . '.json';
        $data = json_decode(Storage::get($jsonFilePath), true);
        return self::fromArray($data);
    }

    // ########################################################################

    /**
     * @param array $data
     * @return MixNode
     * @throws \Exception
     */
    public static function fromArray(array $data): self
    {
        $out = new static(); // TODO fix abstract

        $out->pk = PublicKey::fromArray($data['election_public_key']); // TODO

        $out->challengeBits = $data['challenge_bits'];

        $out->primaryMix = Mix::fromArray($out->pk, $data['primary_mix']);

        $out->shadowMixes = array_map(function (array $shadowMixAray) use ($out) {
            return Mix::fromArray($out->pk, $shadowMixAray);
        }, $data['shadow_mixes']);

        return $out;
    }

    /**
     * @param bool $storePrivateValues
     * @return array
     */
    public function toArray(bool $storePrivateValues = false): array
    {
        return [
            'election_public_key' => $this->pk->toArray(),
            'challenge_bits' => $this->challengeBits,
            'primary_mix' => $this->primaryMix->toArray($storePrivateValues),
            'shadow_mixes' => array_map(function (Mix $shadowMix) use ($storePrivateValues) {
                return $shadowMix->toArray($storePrivateValues);
            }, $this->shadowMixes)
        ];
    }

    // ########################################################################

    /**
     * @param \App\Models\Election $election
     */
    public static function afterVotingPhaseEnds(Election &$election)
    {
        Log::debug('MixNode afterVotingPhaseEnds > dispatching GenerateMix');
        // dispatch mix job
        GenerateMix::dispatch($election);
    }

}
