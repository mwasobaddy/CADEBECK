<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the authenticated user's client the current tenant for the request.
 *
 * The multitenancy package's own tenant finder runs during application boot,
 * which is before the session and auth middleware have run, so the client is
 * resolved here instead - after the session is available.
 *
 * Users with a null client_id (platform staff such as Developer and System
 * Admin) deliberately have no current tenant. Their access is governed by
 * permissions and the `access_all_clients` permission.
 */
class SetCurrentClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->client_id) {
            $client = Client::find($user->client_id);

            if ($client) {
                $client->makeCurrent();
            }
        }

        return $next($request);
    }
}
