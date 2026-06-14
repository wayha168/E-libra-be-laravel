<?php

namespace App\Http\Controllers\View;

use Illuminate\View\View;

class AppNotificationController
{
    public function index(): View
    {
        return view('dashboard.account.notifications.index');
    }
}
