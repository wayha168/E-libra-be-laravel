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

        // Scramble /docs/api — local always; production when SCRAMBLE_DOCS_ENABLED=true
        Gate::define('viewApiDocs', function () {
            if (app()->environment('local')) {
                return true;
            }

            return (bool) config('elibra.scramble_docs_enabled', false);
        });
    }
}
