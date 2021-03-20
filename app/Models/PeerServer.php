<?php

namespace App\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Http;

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
 * @method static find(array $array)
 * @method static self firstOrFail()
 * @method static self|Builder withDomain(string $domain)
 */
class PeerServer extends Model
{
    use SpatialTrait;
    use HasFactory;

    protected $fillable = [
        'name',
        'ip',
        'gps'
    ];

    protected $spatialFields = [
        'gps',
    ];

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
     *
     */
    public function getGps(): bool
    {
        // works with domains too
        $url = "http://ip-api.com/php/{$this->ip}?fields=status,lat,lon";

        $response = Http::get($url);

        if (!$response->status() === 200) {
            return false;
        }

        $data = unserialize($response->body());

        if ($data["status"] === "success") {
            $this->gps = new Point($data["lat"], $data["lon"]);
            return $this->save();
        }

        return false;

    }

}
