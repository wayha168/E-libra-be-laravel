<?php

namespace App\Http\Controllers\View;

use App\Support\AuthorEarnings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorEarningsController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $earnings = AuthorEarnings::forUser($user);

        return view('dashboard.earnings.index', compact('earnings', 'user'));
    }
}
