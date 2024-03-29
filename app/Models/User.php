<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 * @package App\Models
 * @property int id
 * @property string name
 * @property bool is_admin
 * @property bool can_create_election
 * @property string email
 * @property string provider
 * @property string provider_id
 * @method static self|Builder inRandomOrder()
 * @method static self create(array $data)
 * @method static UserFactory factory()
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;
    use HasShareableFields;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'can_create_election',
        'is_admin'
    ];

    public $shareableFields = [
        'email',
        'password',
        'provider',
        'provider_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'bool',
        'can_create_election' => 'bool',
    ];

    // ############################## SCOPES ######################################

    /**
     * @param string $provider
     * @param string $providerID
     * @param string $email
     * @return User|null
     * @noinspection PhpIncompatibleReturnTypeInspection
     * @throws \Exception
     */
    public static function findByProvider(string $provider, string $providerID, string $email): ?User
    {

        if (self::query()
            ->where('email', '=', $email)
            ->where(function (Builder $query) use ($provider, $providerID) {
                return $query
                    ->orWhere('provider', '<>', $provider)
                    ->orWhere('provider_id', '<>', $providerID);
            })
            ->first()) {
            throw new Exception('Email exists already');
        }

        return self::query()
            ->where('provider', '=', $provider)
            ->where('provider_id', '=', $providerID)
            ->first();
    }

    // ############################## RELATIONS ######################################

    /**
     * @return HasMany|Election
     */
    public function administeredElections(): HasMany
    {
        return $this->hasMany(Election::class, 'admin_id');
    }

    /**
     * @return HasManyThrough|Election
     */
    public function votedElections(): HasManyThrough
    {
        return $this->hasManyThrough(
            Election::class,
            Voter::class,
            'user_id',
            'id',
            'id',
            'election_id')
            ->whereNotNull('last_vote_cast_id');
    }

    /**
     * @return HasMany|Trustee
     */
    public function trusteeRoles(): HasMany
    {
        return $this->hasMany(Trustee::class, 'user_id');
    }

    // ############################# JWT #######################################

    /**
     * @return string
     */
    public function getNewJwtToken(): string
    {
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return auth('user_api')->login($this);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();  // id
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
