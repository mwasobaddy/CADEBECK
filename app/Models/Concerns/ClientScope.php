<?php

namespace App\Models\Concerns;

use App\Services\ClientContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts a model to the rows belonging to the current client.
 *
 * Behaviour:
 *  - a user holding `access_all_clients` sees every client's rows
 *  - a user belonging to a client sees only that client's rows
 *  - an authenticated user with no client and no `access_all_clients`
 *    (e.g. a System Admin) sees nothing, rather than everything
 *  - an unauthenticated context (console commands, seeders, queue workers
 *    with no tenant) is left unscoped so system work keeps functioning
 */
class ClientScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (ClientContext::canAccessAllClients()) {
            return;
        }

        $clientId = ClientContext::currentClientId();

        if ($clientId !== null) {
            $builder->where($model->qualifyColumn('client_id'), $clientId);

            return;
        }

        if (! ClientContext::hasAuthenticatedUser()) {
            return;
        }

        // The authenticatable model is always allowed to see its own record.
        //
        // Session guards resolve the user by email through the Eloquent user
        // provider, so scoping it away from the signed-in user would break
        // authentication itself (password confirmation, "remember me",
        // re-hydrating the session). Platform staff have a null client_id, so
        // without this they would be unable to read even their own row.
        if ($model instanceof Authenticatable) {
            $builder->where(
                $model->qualifyColumn($model->getKeyName()),
                auth()->user()?->getAuthIdentifier()
            );

            return;
        }

        // Fail closed: authenticated, but without a client and without
        // permission to cross clients. Return no rows instead of all rows.
        $builder->whereRaw('1 = 0');
    }
}
