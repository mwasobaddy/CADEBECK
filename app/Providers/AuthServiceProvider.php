<?php

namespace App\Providers;

use App\Models\LeaveRequest;
use App\Models\WellBeingResponse;
use App\Policies\LeaveRequestPolicy;
use App\Policies\WellBeingPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        LeaveRequest::class => LeaveRequestPolicy::class,
        WellBeingResponse::class => WellBeingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
