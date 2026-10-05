<?php

namespace App\Services;

use App\Models\Client;

/**
 * Resolves which client the current execution context belongs to.
 *
 * Resolution order:
 *   1. an explicit override (tests / cross-client tooling)
 *   2. the current tenant set by the multitenancy package (SetCurrentClient middleware)
 *   3. the authenticated user's client_id
 *
 * When none of those apply (console commands, seeders, queue workers without a
 * tenant) the context is considered unscoped so that system work keeps working.
 */
class ClientContext
{
    /**
     * Reserved permission "team" id for platform staff (Developer, System
     * Admin). They have no client, but Spatie's team pivot columns are NOT
     * NULL, so their role assignments are stored under this id.
     */
    public const PLATFORM_TEAM_ID = 0;

    protected static ?int $override = null;

    protected static bool $bypass = false;

    /**
     * The client id that owns the current context, or null when unscoped.
     */
    public static function currentClientId(): ?int
    {
        if (static::$override !== null) {
            return static::$override;
        }

        return Client::current()?->getKey() ?? auth()->user()?->client_id;
    }

    /**
     * Whether a user is authenticated for this context.
     */
    public static function hasAuthenticatedUser(): bool
    {
        return auth()->check();
    }

    /**
     * Whether the context may see every client's data.
     *
     * This is driven by the `access_all_clients` permission rather than a role
     * name, in keeping with the application's permission-based authorization.
     */
    public static function canAccessAllClients(): bool
    {
        if (static::$bypass) {
            return true;
        }

        return static::hasAuthenticatedUser()
            && (bool) auth()->user()?->can('access_all_clients');
    }

    public static function bypass(bool $enabled = true): void
    {
        static::$bypass = $enabled;
    }

    public static function override(?int $clientId): void
    {
        static::$override = $clientId;
    }

    /**
     * The team id used by Spatie's permission teams feature.
     *
     * Always an integer: the current client, or the reserved platform id for
     * users who do not belong to a client.
     */
    public static function permissionTeamId(): int
    {
        return static::currentClientId() ?? self::PLATFORM_TEAM_ID;
    }

    /**
     * Reset all static state. Call this between tests.
     */
    public static function flush(): void
    {
        static::$override = null;
        static::$bypass = false;
    }
}
