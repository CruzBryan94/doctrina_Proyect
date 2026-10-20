<?php

namespace App\Providers;

use App\Models\Manual;
use App\Policies\ManualPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [Manual::class => ManualPolicy::class];

    public function boot(): void
    {
        Gate::before(function ($user) {
            if (! $user->is_active) {
                return false;
            }

            return $user->hasRole('admin') ? true : null;
        });
    }
}
