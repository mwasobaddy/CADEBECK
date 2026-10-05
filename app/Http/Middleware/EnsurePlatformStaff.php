<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to platform staff, who belong to no client.
 *
 * The platform area is gated on permissions such as clients.view, but a
 * permission on its own is not enough: a misconfigured or over-broad grant
 * would let a client user reach a screen listing every client. Requiring the
 * absence of a client makes the boundary structural rather than incidental.
 */
class EnsurePlatformStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if(
            ! $user || $user->client_id !== null,
            403,
            __('Platform area is restricted to staff who do not belong to a client.')
        );

        return $next($request);
    }
}