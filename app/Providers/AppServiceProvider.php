<?php

namespace App\Providers;

use App\Models\Country;
use App\Models\CryptoNetwork;
use App\Models\Currency;
use App\Models\DeliveryMethod;
use App\Models\Service;
use App\Policies\ReferenceDataPolicy;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Model-named policies (TransferPolicy, ReceiptPolicy, ...) are auto-discovered.
        foreach ([Country::class, Currency::class, CryptoNetwork::class, Service::class, DeliveryMethod::class] as $model) {
            Gate::policy($model, ReferenceDataPolicy::class);
        }

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(\App\Models\AuditLog::class, \App\Policies\AuditLogPolicy::class);
    }
}
