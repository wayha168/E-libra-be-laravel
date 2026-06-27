<?php

namespace App\Providers;

use App\Events\PurchaseStatusUpdated;
use App\Listeners\HandlePurchaseNotifications;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        Scramble::configure()
            ->withDocumentTransformers(function (\Dedoc\Scramble\Support\Generator\OpenApi $openApi) {
                $openApi->info->title = 'e-libra API';
            });

        Gate::define('viewApiDocs', fn () => app()->environment('local'));
    }
}
