<?php

namespace App\Models;

use App\Http\Middleware\AuthenticateWithElectionCreatorJwt;
use App\Models\Cast\ModelWithFieldsWithParameterSets;
use App\Models\Cast\PublicKeyCasterCryptosystem;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tymon\JWTAuth\Contracts\JWTSubject;

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
 * @property string|null token Token used by the current server to authenticate with the server represented by this model
 *
 * @property \Illuminate\Support\Collection|\App\Models\Election[] elections
 *
 * @method static self|null find(array $array)
 * @method static self firstOrFail()
 * @method static self|Builder unknown()
 * @method static self|Builder withDomain(string $domain)
 * @method static self|null first()
 */
class PeerServer extends Authenticatable implements JWTSubject
{

    use ModelWithFieldsWithParameterSets;
    use SpatialTrait;
    use HasFactory;
    use HasShareableFields;

    protected $fillable = [
        'name',
        'ip',
        //
        'gps',
        'country_code',
        //
        'jwt_public_key',
        'token'
    ];

    public $shareableFields = [
        'domain'
    ];

    protected $spatialFields = [
        'gps',
    ];

    protected $casts = [
        'jwt_public_key' => PublicKeyCasterCryptosystem::class
    ];

    // ############################################# Scopes

    /**
     * Returns peer servers the current server should present himself to
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     * @noinspection PhpUnused
     */
    public static function scopeUnknown(Builder $builder): Builder
    {
        return $builder->whereNull('token');
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
     * @return HasManyThrough|Election
     */
    public function elections(): HasManyThrough
    {
        return $this->hasManyThrough(
            Election::class, Trustee::class,
            'peer_server_id', 'id',
            null, 'election_id');
    }

    // #############################

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
        if ($data['status'] === 'success') {
            $this->gps = new Point($data['lat'], $data['lon']);
            $this->country_code = $data['countryCode'];
            return true;
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

        if (!$token->hasClaim(AuthenticateWithElectionCreatorJwt::UserIdClaimName)) {
            return null;
        }

        return intval($token->getClaim(AuthenticateWithElectionCreatorJwt::UserIdClaimName));

    }

    // ############################# JWT #######################################

    public function getJWTIdentifier()
    {
        return $this->getKey();  // id
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
