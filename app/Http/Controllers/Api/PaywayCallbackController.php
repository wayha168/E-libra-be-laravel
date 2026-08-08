<?php

namespace App\Http\Controllers\Api;

use App\Events\PurchaseStatusUpdated;
use App\Models\UserBuyBook;
use App\Support\PurchaseCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaywayCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tranId = (string) ($request->input('tran_id')
            ?? $request->input('tranId')
            ?? $request->query('tran_id')
            ?? '');

        $purchaseId = null;
        $returnParams = (string) ($request->input('return_params') ?? $request->query('return_params') ?? '');
        if (preg_match('/purchase_id=([0-9a-fA-F-]{36})/', $returnParams, $m)) {
            $purchaseId = $m[1];
        }
        $purchaseId = $purchaseId ?: ($request->input('purchase_id') ?? $request->query('purchase_id'));

        $status = strtolower((string) (
            $request->input('status')
            ?? $request->input('payment_status')
            ?? $request->query('status')
            ?? ''
        ));

        $purchase = null;
        if ($purchaseId) {
            $purchase = UserBuyBook::find($purchaseId);
        }
        if (! $purchase && $tranId !== '') {
            $purchase = UserBuyBook::query()->where('payway_tran_id', $tranId)->first();
        }

        if (! $purchase) {
            Log::warning('PayWay callback: purchase not found', [
                'tran_id' => $tranId,
                'purchase_id' => $purchaseId,
                'payload' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Purchase not found.',
            ], 404);
        }

        // ABA often returns status "0" for success; treat explicit failures only.
        $failed = in_array($status, ['failed', 'cancelled', 'canceled', '2', 'decline', 'declined'], true);
        if ($failed) {
            if ($purchase->status !== 'paid') {
                $purchase->update(['status' => 'failed']);
                event(new PurchaseStatusUpdated($purchase->fresh()));
                DashboardOverviewController::broadcastStats();
            }

            return response()->json([
                'message' => 'Payment marked as failed.',
                'data' => $purchase->fresh(),
            ]);
        }

        if ($purchase->status === 'paid') {
            return response()->json([
                'message' => 'Purchase already paid.',
                'data' => $purchase,
            ]);
        }

        $purchase->update([
            'status' => 'paid',
            'purchased_at' => now(),
            'payment_method' => $purchase->payment_method ?: 'payway_khqr',
            'payway_tran_id' => $purchase->payway_tran_id ?: ($tranId !== '' ? $tranId : null),
        ]);

        $purchase = PurchaseCommission::applyToPurchase($purchase->fresh());
        event(new PurchaseStatusUpdated($purchase));
        DashboardOverviewController::broadcastStats();

        return response()->json([
            'message' => 'PayWay payment confirmed.',
            'data' => [
                'purchase' => $purchase,
                'admin_commission_amount' => $purchase->admin_commission_amount,
                'author_earnings' => $purchase->authorEarnings(),
            ],
        ]);
    }
}
