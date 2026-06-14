<?php

namespace App\Http\Controllers\Api;

use App\Support\AuthorEarnings;
use App\Support\DashboardCharts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorEarningsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $period = $request->string('period', '6m')->toString();
        if (!in_array($period, ['7d', '30d', '6m', '12m'], true)) {
            $period = '6m';
        }

        $user->loadMissing('authorProfile.books');
        $bookIds = $user->authorProfile?->books()->pluck('id')->all() ?? [];

        return response()->json([
            'message' => 'Author earnings fetched successfully',
            'data' => array_merge(
                AuthorEarnings::forUser($user),
                [
                    'charts' => DashboardCharts::authorIncomeForBooks($bookIds, $period),
                ]
            ),
        ]);
    }
}
