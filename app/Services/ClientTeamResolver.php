<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Resolves the Spatie permission "team" id from the current client context.
 *
 * Spatie's teams feature stores the team id on `roles`, `model_has_roles` and
 * `model_has_permissions`. This application calls that team a client, so the
 * configured team_foreign_key is `client_id` and the value is simply the current
 * client.
 *
 * Platform staff (Developer, System Admin) do not belong to a client, but the
 * pivot columns are NOT NULL, so they are stored under the reserved team id 0.
 */
class ClientTeamResolver implements PermissionsTeamResolver
{
    protected int|string|null $teamId = null;

    /**
     * Explicitly set the team id. Used by seeders, impersonation and tests to
     * override the ambient context.
     *
     * @param  int|string|Model|null  $id
     */
    public function setPermissionsTeamId($id): void
    {
        $this->teamId = $id instanceof Model ? $id->getKey() : $id;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        return ClientContext::permissionTeamId();
    }
}
