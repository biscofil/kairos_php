<?php

namespace App\Http\Middleware;

use App\Exceptions\ElectionMustBeFrozen;
use App\Models\Election;
use Closure;
use Illuminate\Http\Request;

/**
 * Class ElectionFrozen
 * @package App\Http\Middleware
 */
class ElectionFrozen
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws \App\Exceptions\ElectionMustBeFrozen
     */
    public function handle(Request $request, Closure $next)
    {

        $election = $request->route('election');

        if ($election && $election instanceof Election) {

            if (is_null($election->frozen_at)) {
                throw new ElectionMustBeFrozen();
            }

        }

        return $next($request);
    }
}
