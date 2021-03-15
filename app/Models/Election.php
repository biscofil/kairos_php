<?php

namespace App\Models;

use App\Crypto\EGKeyPair;
use App\Crypto\EGPrivateKey;
use App\Crypto\EGPublicKey;
use App\Models\Cast\EGPrivateKeyCaster;
use App\Models\Cast\EGPublicKeyCaster;
use App\Models\Cast\ModelWithCryptoFields;
use Carbon\Carbon;
use Database\Factories\ElectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class Election
 * @package App\Models
 * @property int id
 * @property string uuid
 * @property string name
 * @property string slug
 * @property string description
 * @property string help_email
 * @property string info_url
 * @property bool is_private
 * @property bool is_featured
 * @property null|array questions
 *
 * @property string eligibility
 * @property int|null category_id
 * @property Category|null category
 *
 * @property int admin_id
 * @property User admin
 *
 * @property null|EGPublicKey public_key
 * @property null|EGPrivateKey private_key
 *
 * @property null|Carbon frozen_at
 * @property null|Carbon archived_at
 * @property Collection|Trustee[] trustees
 * @property array issues
 *
 * @property bool use_voter_alias
 * @property bool use_advanced_audit_features
 * @property bool randomize_answer_order
 *
 * @method static self create(array $data)
 * @method static self make(array $data)
 * @method static self|null find($id)
 * @method static self findOrFail($id)
 * @method static ElectionFactory factory()
 * @method static self|Builder featured()
 */
class Election extends Model
{
    use HasFactory;
    use ModelWithCryptoFields;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'help_email',
        'info_url',
        'is_private',
        'is_featured',
        'questions',
        //
        'public_key', 'private_key',
        //
        'is_registration_open', // TODO
        'use_voter_alias',
        'use_advanced_audit_features',
        'randomize_answer_order',
        //
        'registration_starts_at',
        'voting_starts_at',
        'voting_started_at',
        'voting_extended_until',
        'voting_end_at',
        'voting_ended_at',
        //
        'tallying_started_at',
        'tallying_finished_at',
        'tallying_combined_at',
        'results_released_at',
        //
        'frozen_at',
        'archived_at',
        //
        'eligibility',
        'category_id'
    ];

    protected $casts = [
        'id' => 'int',
        'public_key' => EGPublicKeyCaster::class,
        'private_key' => EGPrivateKeyCaster::class,
        'questions' => 'json',
        //
        'is_private' => 'bool',
        'is_featured' => 'bool',
        // TODO 'eligibility' => ,
        'use_voter_alias' => 'bool',
        'use_advanced_audit_features' => 'bool',
        'randomize_answer_order' => 'bool',
        //
        'registration_starts_at' => 'datetime',
        'voting_starts_at' => 'datetime',
        'voting_started_at' => 'datetime',
        'voting_extended_until' => 'datetime',
        'voting_end_at' => 'datetime',
        'voting_ended_at' => 'datetime',
        //
        'tallying_started_at' => 'datetime',
        'tallying_finished_at' => 'datetime',
        'tallying_combined_at' => 'datetime',
        'results_released_at' => 'datetime',
        //
        'frozen_at' => 'datetime',
        'archived_at' => 'datetime',
        //
        'has_helios_trustee' => 'bool'
    ];

    protected $appends = [
        'is_auth_user_admin',
        'is_auth_user_trustee',
        'trustee_count',
        'voter_count',
        'cast_votes_count',
        'admin_name',
        'has_system_trustee',
        'issues'
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    // ############################################ Attributes

    /**
     * If the user is logged in, it returns a bool that indicates if the user is the creator of the election,
     * null if not logged
     * @return bool|null
     * @noinspection PhpUnused
     */
    public function getIsAuthUserAdminAttribute(): ?bool
    {
        if (isLogged()) {
            return getAuthUser()->id == $this->admin_id;
        }
        return null;
    }
    /**
     * If the user is logged in, it returns a bool that indicates if the user is a trustee of the election,
     * null if not logged
     * @return bool|null
     * @noinspection PhpUnused
     */
    public function getIsAuthUserTrusteeAttribute(): ?bool
    {
        return !is_null($this->getAuthTrustee());
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getTrusteeCountAttribute(): int
    {
        return $this->trustees()->count();
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getVoterCountAttribute(): int
    {
        return 0; // TODO
    }

    /**
     * @return int
     * @noinspection PhpUnused
     */
    public function getCastVotesCountAttribute(): int
    {
        return 0; // TODO
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getHasSystemTrusteeAttribute(): bool
    {
        return $this->trustees()->systemTrustees()->count() > 0;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAdminNameAttribute(): ?string
    {
        if (is_null($this->admin_id)) {
            return null;
        }
        return $this->admin->name;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getIssuesAttribute(): array
    {
        $issues = [];

        if (is_null($this->questions) || count($this->questions) == 0) {
            $issues[] = [
                'type' => 'questions',
                'action' => "add questions to the ballot"
            ];
        }

        if ($this->trustees()->count() == 0) {
            $issues[] = [
                'type' => 'trustees',
                'action' => "add at least one trustee"
            ];
        } else {
            foreach ($this->trustees as $trustee) {
                if (is_null($trustee->public_key)) {
                    $issues[] = [
                        'type' => 'trustee keypairs',
                        'action' => 'have trustee ' . $trustee->user->name . ' generate a keypair'
                    ];
                }

            }
        }

        if ($this->voters()->count() == 0) { // TODO and not self.reg = open:
            $issues[] = [
                'type' => 'voters',
                'action' => 'enter your voter list (or open registration to the public)'
            ];
        }

        return $issues;
    }

    // ############################################ Scopes

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function scopeFeatured(Builder $builder): Builder
    {
        return $builder->where('is_featured', '=', 1);
    }

    // ############################################ Relations

    /**
     * @return BelongsTo|User
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * @return HasMany|Trustee
     */
    public function trustees(): HasMany
    {
        return $this->hasMany(Trustee::class, 'election_id');
    }

    /**
     * @return HasMany|Voter
     */
    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class, 'election_id');
    }

    /**
     * @return BelongsTo|Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // ############################################

    /**
     * Returns a public key which is the combination (product) of the public keys of the trustees
     * @return EGPublicKey
     * @throws \Exception
     */
    public function generateCombinedPublicKey(): EGPublicKey
    {
        return $this->trustees()->get()->reduce(function (?EGPublicKey $carry, Trustee $trustee): EGPublicKey {
            return $trustee->public_key->combine($carry);
        });
    }

    /**
     * @return EGPrivateKey
     * @throws \Exception
     */
    public function generateCombinedPrivateKey(): EGPrivateKey
    {
        /** @var EGPrivateKey $out */
        $out = $this->trustees()->get()->reduce(function (?EGPrivateKey $carry, Trustee $trustee): EGPrivateKey {
            return $trustee->private_key->combine($carry);
        });
        $out->pk = $this->public_key;
        return $out;
    }

    /**
     * @return string
     */
    public function generateVotersHash(): string
    {
        return "";
        // TODO Sort email addresses of voters and hash them
    }

    /**
     * @throws \Exception
     */
    public function freeze()
    {

        $this->frozen_at = now();

        // generate combined public key
        $this->public_key = $this->generateCombinedPublicKey();

        # generate voters hash
        // TODO $this->generateVotersHash();

        $this->save();

    }

    /**
     * Returns the Trustee corresponding to the auth user
     * @return Trustee|null
     */
    public function getAuthTrustee(): ?Trustee
    {
        if (!isLogged()) {
            return null;
        }
        return $this->trustees()
            ->where('user_id', '=', getAuthUser()->id)
            ->first();
    }

    /**
     * Returns the Voter corresponding to the auth user
     * @return Voter|null
     */
    public function getAuthVoter(): ?Voter
    {
        if (!isLogged()) {
            return null;
        }
        return $this->voters()
            ->where('user_id', '=', getAuthUser()->id)
            ->first();
    }

    /**
     * @param User $user
     * @return Trustee
     */
    public function createTrustee(User $user): Trustee
    {
        $trustee = Trustee::make();
        $trustee->uuid = (string)Str::uuid();
        $trustee->user()->associate($user);
        $trustee->election()->associate($this);
        $trustee->save();
        return $trustee;
    }

    /**
     * @param User $user
     * @return Voter
     */
    public function createVoter(User $user): Voter
    {
        $voter = Voter::make();
        $voter->user()->associate($user);
        $voter->election()->associate($this);
        $voter->save();
        return $voter;
    }

    /**
     * @return Trustee
     */
    public function createSystemTrustee(): Trustee
    {

        $keyPair = EGKeyPair::generate();

        $trustee = Trustee::make();
        $trustee->uuid = (string)Str::uuid();
        $trustee->public_key = $keyPair->pk;
        $trustee->computePublicKeyHash();
        $trustee->private_key = $keyPair->sk;
        $trustee->pok = $trustee->private_key->proveSecretKey([EGPrivateKey::class, 'DLogChallengeGenerator']);
        $trustee->election()->associate($this);
        $trustee->save();
        return $trustee;
    }

    /**
     * @return bool
     */
    public function hasOpenRegistration(): bool
    {
        return true; // TODO eligibility
    }

    /**
     * @param bool $featured
     */
    public function setFeatured(bool $featured): void
    {
        $this->is_featured = $featured;
    }

    /**
     * @param bool $archived
     */
    public function setArchived(bool $archived): void
    {
        $this->archived_at = $archived ? now() : null;
    }

    /**
     * @return Election
     */
    public function duplicate(): Election
    {
        $e = new Election();
        $e->uuid = (string)Str::uuid();
        $e->admin()->associate(getAuthUser());

        $e->name = "Copy of " . $this->name;
        $e->slug = $e->uuid;
        $e->description = $this->description;
        $e->help_email = $this->help_email;
        $e->info_url = $this->info_url;
        // TODO $e->is_registration_open =
        $e->use_voter_alias = $this->use_voter_alias;
        $e->use_advanced_audit_features = $this->use_advanced_audit_features;
        $e->randomize_answer_order = $this->randomize_answer_order;

        $e->save();
        return $e;
    }

}
