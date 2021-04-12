<?php

namespace App\Models;

use App\Http\Middleware\AuthenticateWithElectionCreatorJwt;
use App\Models\Cast\ModelWithCryptoFields;
use App\Models\Cast\PublicKeyCasterCryptosystem;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

/**
 * Class PeerServer
 * @package App\Models
 *
 * @property int id
 * @property string name
 * @property string ip
 * @property int election_id
 * @property Election election
 * @property Point|null gps
 * @property string|null country_code
 *
 * @property RSAPublicKey|null jwt_public_key
 *
 * @method static find(array $array)
 * @method static self firstOrFail()
 * @method static self|Builder withDomain(string $domain)
 */
class PeerServer extends Model
{

    use ModelWithCryptoFields;
    use SpatialTrait;
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        //
        'gps',
        'country_code',
        //
        'jwt_public_key'
    ];

    protected $spatialFields = [
        'gps',
    ];

    protected $casts = [
        'jwt_public_key' => PublicKeyCasterCryptosystem::class
    ];

    /**
     * @param string $domain
     * @return PeerServer|null
     */
    public static function fromDomain(string $domain): ?PeerServer
    {
        return self::withDomain($domain)->first();
    }

    /**
     * @param Builder $builder
     * @param string $domain
     * @return Builder
     * @throws \Exception
     * @noinspection PhpIncompatibleReturnTypeInspection
     * @noinspection PhpUnused
     */
    public static function scopeWithDomain(Builder $builder, string $domain): Builder
    {
        return $builder->where('ip', '=', extractDomain($domain));
    }

    // ############################################# RELATIONS

    /**
     * @return BelongsToMany
     */
    public function elections(): BelongsToMany
    {
        return $this->belongsToMany(Election::class, 'election_peer_servers');
    }

    /**
     * @param bool $selfQuery
     * @return bool
     */
    public function fetchServerInfo(bool $selfQuery = false): bool
    {
        $v = $selfQuery ? "" : $this->ip; // works with both ip/domain
        /** @noinspection HttpUrlsUsage */
        $url = "http://ip-api.com/php/$v?fields=status,lat,lon,countryCode,query";
        $response = Http::get($url);
        if (!($response->status() === 200)) {
            return false;
        }
        $data = unserialize($response->body());
        if ($data["status"] === "success") {
            $this->gps = new Point($data["lat"], $data["lon"]);
            $this->country_code = $data["countryCode"];
            if ($selfQuery) {
                $this->ip = $data["query"];
            }
            return $this->save();
        }
        return false;
    }

    /**
     * @param string $tokenStrReceived
     * @return int|null
     */
    public function checkJwtTokenAndReturnUserID(string $tokenStrReceived): ?int
    {
        $signer = new Sha256();

        $token = (new Parser())->parse($tokenStrReceived);

        $publicKey = new Key($this->jwt_public_key->toArray()['v']);

        if (!$token->verify($signer, $publicKey)) {
            return null;
        }

        $claims = $token->getClaims();

        if (!array_key_exists(AuthenticateWithElectionCreatorJwt::UserIdClaimName, $claims)) {
            return null;
        }

        return intval($claims[AuthenticateWithElectionCreatorJwt::UserIdClaimName]);

    }

}
