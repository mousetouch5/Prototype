<?php

namespace App\Providers;

use App\Models\ClickUpAccount;
use App\Models\MondayAccount;
use App\Models\SyncConfiguration;
use App\Policies\ClickUpAccountPolicy;
use App\Policies\MondayAccountPolicy;
use App\Policies\SyncConfigurationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ClickUpAccount::class => ClickUpAccountPolicy::class,
        MondayAccount::class => MondayAccountPolicy::class,
        SyncConfiguration::class => SyncConfigurationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}