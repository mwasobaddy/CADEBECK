<?php

namespace App\Models\Concerns;

use App\Models\Client;
use App\Services\ClientContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks a model as belonging to a client.
 *
 * Adds the `client_id` global scope, auto-fills `client_id` on create, and
 * exposes the `client()` relationship plus helpers for cross-client queries.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToClient
{
    public static function bootBelongsToClient(): void
    {
        static::addGlobalScope(new ClientScope);

        static::creating(function ($model) {
            $contextClientId = ClientContext::currentClientId();

            if ($contextClientId !== null) {
                // Inside a client context the tenant is always enforced, so a
                // client user cannot assign a record to a different client.
                $model->client_id = $contextClientId;
            }
        });
    }

    /**
     * The client this record belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * A query across every client, ignoring the current context.
     *
     * Only for cross-client reporting and system administration. Anything
     * returned here must still be filtered by permission.
     */
    public static function acrossClients(): Builder
    {
        return static::query()->withoutGlobalScope(ClientScope::class);
    }

    /**
     * Restrict a query to an explicit client, regardless of the current context.
     */
    public static function forClient(int $clientId): Builder
    {
        return static::query()
            ->withoutGlobalScope(ClientScope::class)
            ->where((new static())->qualifyColumn('client_id'), $clientId);
    }
}
