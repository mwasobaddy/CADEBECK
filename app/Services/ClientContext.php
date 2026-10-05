<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

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
 *
 * IMPORTANT: this class is consulted from inside the client global scope, which
 * means it runs while the session guard may itself be loading the signed-in user
 * from the database. The guard only caches the user *after* retrieving it, and
 * both auth()->user() and auth()->id() go through that retrieval, so consulting
 * the guard from inside the scope re-enters the scope indefinitely: every
 * authenticated request then died after the maximum execution time.
 *
 * Two safeguards prevent that:
 *
 *  - all guard access goes through guardUser(), which refuses to recurse. While
 *    the guard is fetching the user, nested calls get null and the scope stands
 *    down (see ClientScope), letting the lookup complete.
 *  - the user's client id is read straight from the table, so it never depends on
 *    resolving the user through the scoped model.
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
     * True while the session guard is being asked for the signed-in user.
     *
     * @var bool
     */
    protected static bool $resolvingGuardUser = false;

    /**
     * Guard against re-entering permission resolution from inside a scope.
     *
     * @var bool
     */
    protected static bool $resolvingPermissions = false;

    /**
     * Memoised client id per user id, so one request reads it at most once.
     *
     * @var array<int, int|null>
     */
    protected static array $clientIdByUser = [];

    /**
     * The client id that owns the current context, or null when unscoped.
     */
    public static function currentClientId(): ?int
    {
        if (static::$override !== null) {
            return static::$override;
        }

        $current = Client::current()?->getKey();

        if ($current !== null) {
            return $current;
        }

        return static::clientIdForAuthenticatedUser();
    }

    /**
     * The signed-in user, or null, without ever recursing into the guard.
     */
    public static function guardUser(): ?Authenticatable
    {
        if (static::$resolvingGuardUser) {
            return null;
        }

        static::$resolvingGuardUser = true;

        try {
            return auth()->user();
        } finally {
            static::$resolvingGuardUser = false;
        }
    }

    /**
     * Whether the guard is currently fetching the signed-in user.
     *
     * The client scope stands down while this is true, so the lookup the guard
     * performs can complete.
     */
    public static function isResolvingGuardUser(): bool
    {
        return static::$resolvingGuardUser;
    }

    /**
     * The client id of the signed-in user, read without invoking scopes.
     */
    protected static function clientIdForAuthenticatedUser(): ?int
    {
        $user = static::guardUser();

        if ($user === null) {
            return null;
        }

        $userId = (int) $user->getAuthIdentifier();

        if (! array_key_exists($userId, static::$clientIdByUser)) {
            static::$clientIdByUser[$userId] = DB::table('users')
                ->where('id', $userId)
                ->value('client_id');
        }

        return static::$clientIdByUser[$userId];
    }

    /**
     * Whether a user is authenticated for this context.
     */
    public static function hasAuthenticatedUser(): bool
    {
        return static::guardUser() !== null;
    }

    /**
     * Whether the context may see every client's data.
     *
     * Driven by the `access_all_clients` permission rather than a role name, in
     * keeping with the application's permission-based authorization.
     */
    public static function canAccessAllClients(): bool
    {
        if (static::$bypass) {
            return true;
        }

        $user = static::guardUser();

        if ($user === null || static::$resolvingPermissions) {
            return false;
        }

        static::$resolvingPermissions = true;

        try {
            return (bool) $user->can('access_all_clients');
        } finally {
            static::$resolvingPermissions = false;
        }
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

    public static function bypass(bool $enabled = true): void
    {
        static::$bypass = $enabled;
    }

    public static function override(?int $clientId): void
    {
        static::$override = $clientId;
    }

    /**
     * Reset all static state. Call this between tests.
     */
    public static function flush(): void
    {
        static::$override = null;
        static::$bypass = false;
        static::$resolvingGuardUser = false;
        static::$resolvingPermissions = false;
        static::$clientIdByUser = [];
    }
}