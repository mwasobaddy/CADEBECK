<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates a client together with the first user who can administer it.
 *
 * A client is only usable once somebody can log in and add the rest of their
 * staff, so creating one always provisions a Client Admin alongside it.
 *
 * The client context is switched for the duration: Spatie stores the permission
 * team id on the role pivot, so the new user's role has to be assigned while
 * their client is current, otherwise the assignment is filed under the platform
 * team and the user appears to have no roles.
 */
class ClientProvisioner
{
    /**
     * @param  array<string, mixed>  $clientData
     * @param  array<string, mixed>  $ownerData  first_name, other_names, email, password
     */
    public function provision(array $clientData, array $ownerData, ?int $actorId = null): Client
    {
        $role = \Spatie\Permission\Models\Role::where('name', 'Client Admin')->first();

        if (! $role) {
            throw new RuntimeException(
                'The Client Admin role is missing. Run the database seeders before creating clients.'
            );
        }

        return DB::transaction(function () use ($clientData, $ownerData, $actorId, $role) {
            $client = Client::create([
                'name' => $clientData['name'],
                'slug' => $this->uniqueSlug($clientData['name'], $clientData['slug'] ?? null),
                'status' => 'active',
                'contact_email' => $clientData['contact_email'] ?? null,
                'contact_phone' => $clientData['contact_phone'] ?? null,
                'plan' => $clientData['plan'] ?? null,
                'max_users' => $clientData['max_users'] ?? null,
                'timezone' => $clientData['timezone'] ?? config('app.timezone', 'UTC'),
            ]);

            ClientContext::override($client->id);

            try {
                // Assigned explicitly rather than mass assigned: email_verified_at is
                // deliberately not fillable, so a verification timestamp cannot be
                // set from a form payload.
                $owner = new User([
                    'client_id' => $client->id,
                    'first_name' => $ownerData['first_name'],
                    'other_names' => $ownerData['other_names'] ?? null,
                    'email' => $ownerData['email'],
                    'password' => Hash::make($ownerData['password']),
                ]);
                $owner->email_verified_at = now();
                $owner->save();

                $owner->assignRole($role);

                Audit::create([
                    'actor_id' => $actorId,
                    'action' => 'create',
                    'target_type' => Client::class,
                    'target_id' => $client->id,
                    'details' => json_encode([
                        'name' => $client->name,
                        'slug' => $client->slug,
                        'owner_email' => $owner->email,
                    ]),
                ]);
            } finally {
                ClientContext::flush();
                Client::forgetCurrent();
            }

            return $client;
        });
    }

    /**
     * Derive a URL-safe slug, falling back to a suffix when the name repeats.
     */
    protected function uniqueSlug(string $name, ?string $preferred = null): string
    {
        $base = Str::slug($preferred ?: $name) ?: 'client';
        $slug = $base;
        $suffix = 1;

        while (Client::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}