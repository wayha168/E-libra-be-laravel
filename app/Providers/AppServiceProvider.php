<?php

namespace App\Providers;

use App\Events\PurchaseStatusUpdated;
use App\Listeners\HandlePurchaseNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(PurchaseStatusUpdated::class, HandlePurchaseNotifications::class);
    }
}
