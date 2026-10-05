<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\User;

/**
 * Read-only, cross-client overview for platform staff.
 *
 * Every query here deliberately bypasses the client scope with acrossClients()
 * or forClient(). A System Admin has no client of their own, so the ordinary
 * scoped queries would fail closed and silently report zeroes.
 *
 * Nothing in this service writes to client data.
 */
class ClientOverview
{
    /**
     * One row per client, with its headline counts.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function clientSummaries(): \Illuminate\Support\Collection
    {
        return Client::query()
            ->withTrashed()
            ->orderBy('name')
            ->get()
            ->map(function (Client $client) {
                $employeeCount = Employee::forClient($client->id)->count();

                return [
                    'client' => $client,
                    'users' => User::forClient($client->id)->count(),
                    'employees' => $employeeCount,
                    'locations' => Location::forClient($client->id)->count(),
                    'payroll_runs' => Payroll::forClient($client->id)->count(),
                ];
            });
    }

    /**
     * Aggregate totals across every client.
     *
     * @return array<string, int|float>
     */
    public function totals(): array
    {
        $payroll = Payroll::acrossClients();

        return [
            'clients' => Client::withTrashed()->count(),
            'active_clients' => Client::query()->where('status', 'active')->count(),
            'suspended_clients' => Client::query()->where('status', 'suspended')->count(),
            'users' => User::acrossClients()->count(),
            'employees' => Employee::acrossClients()->count(),
            'locations' => Location::acrossClients()->count(),
            'payroll_runs' => (clone $payroll)->count(),
            'gross_pay_total' => (float) (clone $payroll)->sum('gross_pay'),
            'net_pay_total' => (float) (clone $payroll)->sum('net_pay'),
        ];
    }
}