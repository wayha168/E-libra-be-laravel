<?php

namespace App\Listeners;

use App\Events\PurchaseStatusUpdated;
use App\Support\PurchaseNotificationHandler;

class HandlePurchaseNotifications
{
    public function handle(PurchaseStatusUpdated $event): void
    {
        PurchaseNotificationHandler::handle($event->purchase);
    }
}
