<?php

namespace App\Http;

use App\Http\Middleware\ActAsPeer;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateWithElectionCreatorJwt;
use App\Http\Middleware\CanVote;
use App\Http\Middleware\DuringVotingPhase;
use App\Http\Middleware\ElectionFrozen;
use App\Http\Middleware\ElectionNotFrozen;
use App\Http\Middleware\ElectionTrustee;
use App\Http\Middleware\MustBeAdmin;
use App\Http\Middleware\MustBeElectionAdmin;
use App\Http\Middleware\NoElectionIssues;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use Fruitcake\Cors\HandleCors;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        ActAsPeer::class,
        HandleCors::class,
        //RefreshAndReturnToken::class,
        TrustProxies::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
        ],

        'api' => [
            'throttle:api',
            SubstituteBindings::class,
            ConvertEmptyStringsToNull::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => Authenticate::class,
        'authenticate_with_election_creator_jwt' => AuthenticateWithElectionCreatorJwt::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,

        'admin' => MustBeAdmin::class,
        'election_admin' => MustBeElectionAdmin::class,

        'election_trustee' => ElectionTrustee::class,
        'during_voting_phase' => DuringVotingPhase::class,
        'frozen' => ElectionFrozen::class,
        'not_frozen' => ElectionNotFrozen::class,
        'no_issues' => NoElectionIssues::class,
        'can_vote' => CanVote::class,
    ];
}
