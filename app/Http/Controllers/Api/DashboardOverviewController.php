<?php



namespace App\Http\Controllers\Api;



use App\Events\DashboardStatsUpdated;

use App\Models\BookComment;

use App\Models\User;

use App\Models\UserBuyBook;

use App\Support\AuthorDashboardStats;

use App\Support\DashboardStats;

use App\Support\DashboardCharts;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;



class DashboardOverviewController extends Controller

{

    public function index(Request $request): JsonResponse

    {

        $user = $request->user();

        $period = $request->string('period', '6m')->toString();

        if (!in_array($period, ['7d', '30d', '6m', '12m'], true)) {

            $period = '6m';

        }



        if ($user->isAuthor() && !$user->isAdmin() && !$user->isSuperAdmin()) {

            return $this->authorOverview($user, $period);

        }



        if (!$user->isAdmin() && !$user->isSuperAdmin()) {

            return response()->json(['message' => 'Forbidden'], 403);

        }



        $stats = DashboardStats::collect();

        $stats['scope'] = 'admin';



        return response()->json([

            'message' => 'Dashboard overview fetched successfully',

            'data' => [

                'scope' => 'admin',

                'totals' => $stats,

                'charts' => DashboardCharts::build($period),

                'recent_users' => User::query()

                    ->latest()

                    ->take(5)

                    ->get(['id', 'name', 'email', 'created_at']),

                'recent_purchases' => UserBuyBook::query()

                    ->with(['user:id,name,email', 'book:id,title,price'])

                    ->latest()

                    ->take(8)

                    ->get(),

                'recent_comments' => BookComment::query()

                    ->with(['user:id,name', 'book:id,title'])

                    ->latest()

                    ->take(5)

                    ->get(),

            ],

        ]);

    }


    private function authorOverview(User $user, string $period): JsonResponse

    {

        $user->loadMissing('authorProfile.books');

        $bookIds = $user->authorProfile?->books()->pluck('id')->all() ?? [];

        return response()->json([

            'message' => 'Author dashboard overview fetched successfully',

            'data' => [

                'scope' => 'author',

                'totals' => AuthorDashboardStats::collect($user),

                'charts' => [

                    'period' => $period,

                    'labels' => DashboardCharts::authorIncomeForBooks($bookIds, $period)['labels'] ?? [],

                    'author_income' => DashboardCharts::authorIncomeForBooks($bookIds, $period),

                ],

                'recent_sales' => AuthorDashboardStats::recentSales($user),

                'recent_comments' => AuthorDashboardStats::recentComments($user),

            ],

        ]);

    }

    public static function broadcastStats(): void
    {
        DashboardStats::flush();
        DashboardCharts::flush();

        event(new DashboardStatsUpdated(DashboardStats::collect()));
    }

}

