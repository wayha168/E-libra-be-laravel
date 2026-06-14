<?php

namespace App\Http\Controllers\Api;

use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripePaymentService $stripe): JsonResponse
    {
        try {
            $stripe->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Webhook error: ' . $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Webhook handled']);
    }
}
