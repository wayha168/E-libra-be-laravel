<?php

namespace App\Support;

use App\Models\BookComment;
use App\Models\Books;
use App\Models\Category;
use App\Models\User;
use App\Models\UserBuyBook;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardCharts
{
    public static function build(string $period = '6m'): array
    {
        [$start, $labels] = self::resolvePeriod($period);

        return [
            'period' => $period,
            'labels' => $labels,
            'income' => self::incomeSeries($start, $labels, $period),
            'user_registrations' => self::userRegistrationSeries($start, $labels, $period),
            'purchases' => self::purchaseSeries($start, $labels, $period),
            'library' => self::libraryBreakdown(),
            'purchase_status' => self::purchaseStatusBreakdown(),
        ];
    }

    public static function authorIncomeForBooks(array $bookIds, string $period = '6m'): array
    {
        if (empty($bookIds)) {
            [, $labels] = self::resolvePeriod($period);

            return [
                'period' => $period,
                'labels' => $labels,
                'gross' => array_fill(0, count($labels), 0),
                'platform_fee' => array_fill(0, count($labels), 0),
                'net' => array_fill(0, count($labels), 0),
            ];
        }

        [$start, $labels] = self::resolvePeriod($period);
        $daily = self::isDailyPeriod($period);

        $sales = UserBuyBook::query()
            ->whereIn('book_id', $bookIds)
            ->where('status', 'paid')
            ->where('purchased_at', '>=', $start)
            ->whereNotNull('purchased_at')
            ->get(['amount', 'admin_commission_amount', 'purchased_at']);

        if ($daily) {
            $gross = array_fill(0, count($labels), 0.0);
            $fee = array_fill(0, count($labels), 0.0);
            $net = array_fill(0, count($labels), 0.0);
            $idx = array_flip($labels);

            foreach ($sales as $s) {
                $l = Carbon::parse($s->purchased_at)->format('M d');
                if (!isset($idx[$l])) {
                    continue;
                }
                $i = $idx[$l];
                $a = (float) $s->amount;
                $f = (float) ($s->admin_commission_amount ?? 0);
                $gross[$i] += $a;
                $fee[$i] += $f;
                $net[$i] += max(0, $a - $f);
            }

            return [
                'period' => $period,
                'labels' => $labels,
                'gross' => $gross,
                'platform_fee' => $fee,
                'net' => $net,
            ];
        }

        $keys = self::monthKeysFromLabels($labels);
        $gross = array_fill_keys($keys, 0.0);
        $fee = array_fill_keys($keys, 0.0);
        $net = array_fill_keys($keys, 0.0);

        foreach ($sales as $s) {
            $k = Carbon::parse($s->purchased_at)->format('Y-m');
            if (!array_key_exists($k, $gross)) {
                continue;
            }
            $a = (float) $s->amount;
            $f = (float) ($s->admin_commission_amount ?? 0);
            $gross[$k] += $a;
            $fee[$k] += $f;
            $net[$k] += max(0, $a - $f);
        }

        return [
            'period' => $period,
            'labels' => $labels,
            'gross' => array_values($gross),
            'platform_fee' => array_values($fee),
            'net' => array_values($net),
        ];
    }

    private static function resolvePeriod(string $period): array
    {
        if (self::isDailyPeriod($period)) {
            $days = $period === '7d' ? 7 : 30;
            $start = now()->subDays($days - 1)->startOfDay();
            $labels = collect(CarbonPeriod::create($start, '1 day', now()))
                ->map(fn ($d) => $d->format('M d'))
                ->values()
                ->all();

            return [$start, $labels];
        }

        $monthsCount = $period === '12m' ? 12 : 6;
        $start = now()->subMonths($monthsCount - 1)->startOfMonth();
        $labels = collect(range(0, $monthsCount - 1))
            ->map(fn ($i) => now()->subMonths($monthsCount - 1 - $i)->format('M Y'))
            ->all();

        return [$start, $labels];
    }

    private static function incomeSeries(Carbon $start, array $labels, string $period): array
    {
        if (self::isDailyPeriod($period)) {
            return self::dailyIncome($start, $labels);
        }

        $keys = self::monthKeysFromLabels($labels);
        $revenue = array_fill_keys($keys, 0.0);
        $commission = array_fill_keys($keys, 0.0);

        UserBuyBook::query()
            ->where('status', 'paid')
            ->where('purchased_at', '>=', $start)
            ->whereNotNull('purchased_at')
            ->get(['amount', 'admin_commission_amount', 'purchased_at'])
            ->each(function ($row) use (&$revenue, &$commission) {
                $key = Carbon::parse($row->purchased_at)->format('Y-m');
                if (!array_key_exists($key, $revenue)) {
                    return;
                }
                $revenue[$key] += (float) $row->amount;
                $commission[$key] += (float) ($row->admin_commission_amount ?? 0);
            });

        return [
            'revenue' => array_values($revenue),
            'admin_commission' => array_values($commission),
        ];
    }

    private static function dailyIncome(Carbon $start, array $labels): array
    {
        $revenue = array_fill(0, count($labels), 0.0);
        $commission = array_fill(0, count($labels), 0.0);
        $indexByDate = array_flip($labels);

        UserBuyBook::query()
            ->where('status', 'paid')
            ->where('purchased_at', '>=', $start)
            ->whereNotNull('purchased_at')
            ->get(['amount', 'admin_commission_amount', 'purchased_at'])
            ->each(function ($row) use (&$revenue, &$commission, $indexByDate) {
                $label = Carbon::parse($row->purchased_at)->format('M d');
                if (!isset($indexByDate[$label])) {
                    return;
                }
                $i = $indexByDate[$label];
                $revenue[$i] += (float) $row->amount;
                $commission[$i] += (float) ($row->admin_commission_amount ?? 0);
            });

        return [
            'revenue' => $revenue,
            'admin_commission' => $commission,
        ];
    }

    private static function userRegistrationSeries(Carbon $start, array $labels, string $period): array
    {
        if (self::isDailyPeriod($period)) {
            return self::dailyUserRegistrations($start, $labels);
        }

        $keys = self::monthKeysFromLabels($labels);
        $counts = array_fill_keys($keys, 0);

        User::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->each(function ($user) use (&$counts) {
                $key = Carbon::parse($user->created_at)->format('Y-m');
                if (array_key_exists($key, $counts)) {
                    $counts[$key]++;
                }
            });

        return ['registrations' => array_values($counts)];
    }

    private static function dailyUserRegistrations(Carbon $start, array $labels): array
    {
        $counts = array_fill(0, count($labels), 0);
        $indexByDate = array_flip($labels);

        User::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->each(function ($user) use (&$counts, $indexByDate) {
                $label = Carbon::parse($user->created_at)->format('M d');
                if (isset($indexByDate[$label])) {
                    $counts[$indexByDate[$label]]++;
                }
            });

        return ['registrations' => $counts];
    }

    private static function purchaseSeries(Carbon $start, array $labels, string $period): array
    {
        if (self::isDailyPeriod($period)) {
            return self::dailyPurchases($start, $labels);
        }

        $keys = self::monthKeysFromLabels($labels);
        $paid = array_fill_keys($keys, 0);
        $pending = array_fill_keys($keys, 0);

        UserBuyBook::query()
            ->where('created_at', '>=', $start)
            ->get(['status', 'created_at'])
            ->each(function ($row) use (&$paid, &$pending) {
                $key = Carbon::parse($row->created_at)->format('Y-m');
                if (!array_key_exists($key, $paid)) {
                    return;
                }
                if ($row->status === 'paid') {
                    $paid[$key]++;
                } elseif ($row->status === 'pending') {
                    $pending[$key]++;
                }
            });

        return [
            'paid' => array_values($paid),
            'pending' => array_values($pending),
        ];
    }

    private static function dailyPurchases(Carbon $start, array $labels): array
    {
        $paid = array_fill(0, count($labels), 0);
        $pending = array_fill(0, count($labels), 0);
        $indexByDate = array_flip($labels);

        UserBuyBook::query()
            ->where('created_at', '>=', $start)
            ->get(['status', 'created_at'])
            ->each(function ($row) use (&$paid, &$pending, $indexByDate) {
                $label = Carbon::parse($row->created_at)->format('M d');
                if (!isset($indexByDate[$label])) {
                    return;
                }
                $i = $indexByDate[$label];
                if ($row->status === 'paid') {
                    $paid[$i]++;
                } elseif ($row->status === 'pending') {
                    $pending[$i]++;
                }
            });

        return [
            'paid' => $paid,
            'pending' => $pending,
        ];
    }

    private static function libraryBreakdown(): array
    {
        return [
            'labels' => ['Books', 'Categories', 'Comments', 'Users'],
            'values' => [
                Books::count(),
                Category::count(),
                BookComment::count(),
                User::count(),
            ],
        ];
    }

    private static function purchaseStatusBreakdown(): array
    {
        $paid = UserBuyBook::where('status', 'paid')->count();
        $pending = UserBuyBook::where('status', 'pending')->count();
        $other = UserBuyBook::whereNotIn('status', ['paid', 'pending'])->count();

        return [
            'labels' => ['Paid', 'Pending', 'Other'],
            'values' => [$paid, $pending, $other],
        ];
    }

    private static function isDailyPeriod(string $period): bool
    {
        return in_array($period, ['7d', '30d'], true);
    }

    private static function monthKeysFromLabels(array $labels): array
    {
        return collect($labels)
            ->map(fn ($l) => Carbon::createFromFormat('M Y', $l)->format('Y-m'))
            ->all();
    }
}
