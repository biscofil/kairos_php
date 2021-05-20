<?php

namespace App\Models;

use App\Http\Middleware\AuthenticateWithElectionCreatorJwt;
use App\Jobs\SendP2PMessage;
use App\Models\Cast\ModelWithFieldsWithParameterSets;
use App\Models\Cast\PublicKeyCaster;
use App\Models\Cast\SecretKeyCaster;
use App\P2P\Messages\AddMeToYourPeers;
use App\Voting\CryptoSystems\RSA\RSAPublicKey;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
 * @property string $domain
 * @property Point|null gps
 * @property string|null country_code
 *
 * @property int election_id
 * @property Election election
 *
 * @property \App\Voting\CryptoSystems\RSA\RSASecretKey|null jwt_secret_key
 * @property RSAPublicKey|null jwt_public_key
 * @property string|null token Token used by the current server to authenticate with the server represented by this model
 *
 * @property \Illuminate\Support\Collection|\App\Models\Election[] elections
 *
 * @method static self|null find(int|array $array)
 * @method static self firstOrFail()
 * @method static self|Builder unknown()
 * @method static self|Builder ignoreMyself()
 * @method static self|Builder withDomain(string $domain)
 * @method static self|null first()
 * @method static findOrFail($id)
 */
class PeerServer extends Authenticatable implements JWTSubject
{

    public const meID = 1;
    public const PeerServerMeCacheKey = '_current_peer_server_';

    use ModelWithFieldsWithParameterSets;
    use SpatialTrait;
    use HasFactory;
    use HasShareableFields;

    protected $fillable = [
        'id',
        'name',
        'domain',
        //
        'gps',
        'country_code',
        //
        'jwt_secret_key',
        'jwt_public_key',
        'token'
    ];

    public $shareableFields = [
        'domain'
    ];

    protected $spatialFields = [
        'gps',
    ];

    protected $hidden = [
        'jwt_secret_key',
        'token'
    ];

    protected $casts = [
        'jwt_secret_key' => SecretKeyCaster::class,
        'jwt_public_key' => PublicKeyCaster::class
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
        return $builder->whereNull('token')
            ->where('peer_servers.id', '<>', PeerServer::meID); // ignore myself
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return \Illuminate\Database\Eloquent\Builder
     * @noinspection PhpUnused
     */
    public static function scopeIgnoreMyself(Builder $builder): Builder{
        return $builder->where('peer_servers.id', '<>', PeerServer::meID); // ignore myself
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
        $domain = extractDomain($domain);
//        $ip = gethostbyname($domain);
        return $builder->where('domain', '=', $domain);
    }

    // #############################################

    /**
     * @param bool $fail
     * @return \App\Models\PeerServer
     */
    public static function me(bool $fail = true): PeerServer
    {
        return Cache::remember(self::PeerServerMeCacheKey, 15, function () use ($fail) {
            if ($fail) {
                return self::findOrFail(self::meID);
            }
            return self::find(self::meID);
        });
    }

    /**
     * @param string $domain
     * @return \App\Models\PeerServer
     */
    public static function newPeerServer(string $domain): PeerServer
    {
        $senderPeer = new PeerServer();
        $senderPeer->name = 'Server @ ' . $domain;
        $senderPeer->domain = $domain;
        $senderPeer->fetchServerInfo();
        return $senderPeer;
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
     * @param string $toDomain
     * @return \App\Models\PeerServer
     * @throws \Exception
     */
    public static function addPeer(string $toDomain): PeerServer
    {
        $me = self::me();

        $toDomain = extractDomain($toDomain);

        $peerServer = self::withDomain($toDomain)->first();
        if (is_null($peerServer)) {
            $peerServer = self::newPeerServer($toDomain);
            $peerServer->save();
        } else {
            Log::warning('addPeer > Already present');
        }

        SendP2PMessage::dispatch(
            new AddMeToYourPeers\AddMeToYourPeersRequest(
                $me,
                $peerServer,
                PeerServer::me()->jwt_public_key,
                $peerServer->getNewToken()
            )
        );

        Log::debug('addPeer > done');
        return $peerServer;
    }

    // #############################

    /**
     * @return bool
     */
    public function fetchServerInfo(): bool
    {
        $d = $this->domain; // works with both ip/domain
        /** @noinspection HttpUrlsUsage */
        $url = "http://ip-api.com/php/$d?fields=status,lat,lon,countryCode,query";
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

    /**
     * Returns a new JWT token for the peer server
     * @return string
     */
    public function getNewToken(): string
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return auth('peer_api')->setTTL(99999999999999999999999)->login($this); // TODO should not expire
    }
}
