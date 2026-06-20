<?php

namespace App\Services;

use App\Models\Books;
use App\Models\User;
use App\Models\UserBuyBook;
use App\Events\PurchaseStatusUpdated;
use App\Support\PurchaseCommission;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    public function isConfigured(): bool
    {
        return !empty(config('services.stripe.secret'));
    }

    public function createBookCheckoutSession(User $user, Books $book, UserBuyBook $purchase, string $paymentMethod = 'card'): Session
    {
        $this->initStripe();

        $effectivePrice = \App\Support\BookPricing::effectivePrice($book) ?? (float) $book->price;
        $amountCents = (int) round($effectivePrice * 100);

        $params = [
            'mode' => 'payment',
            'customer_email' => $user->email,
            'client_reference_id' => (string) $purchase->id,
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'usd'),
                    'product_data' => [
                        'name' => $book->title,
                        'description' => $book->description ? substr($book->description, 0, 200) : 'e-Libra book purchase',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'success_url' => config('services.stripe.success_url') . '?session_id={CHECKOUT_SESSION_ID}&type=book',
            'cancel_url' => config('services.stripe.cancel_url') . '?type=book&book_id=' . $book->id,
            'metadata' => [
                'type' => 'book_purchase',
                'user_id' => (string) $user->id,
                'book_id' => (string) $book->id,
                'purchase_id' => (string) $purchase->id,
                'payment_method' => $paymentMethod,
            ],
        ];

        if ($paymentMethod === 'khqr') {
            $params['payment_method_types'] = ['card', 'khqr'];
        } else {
            $params['payment_method_types'] = ['card'];
        }

        return Session::create($params);
    }

    public function createSubscriptionCheckoutSession(User $user): Session
    {
        $this->initStripe();

        $amountCents = (int) round((float) config('services.stripe.subscription_amount', 9.99) * 100);

        return Session::create([
            'mode' => 'payment',
            'customer_email' => $user->email,
            'client_reference_id' => (string) $user->id,
            'line_items' => [[
                'price_data' => [
                    'currency' => config('services.stripe.currency', 'usd'),
                    'product_data' => [
                        'name' => 'e-Libra Subscription',
                        'description' => 'Full library access subscription',
                    ],
                    'unit_amount' => $amountCents,
                ],
                'quantity' => 1,
            ]],
            'success_url' => config('services.stripe.success_url') . '?session_id={CHECKOUT_SESSION_ID}&type=subscription',
            'cancel_url' => config('services.stripe.cancel_url') . '?type=subscription',
            'metadata' => [
                'type' => 'subscription',
                'user_id' => (string) $user->id,
            ],
        ]);
    }

    public function handleWebhook(string $payload, ?string $signature): void
    {
        $this->initStripe();

        $secret = config('services.stripe.webhook_secret');

        if ($secret && $signature) {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } else {
            $event = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
        }

        $type = $event->type ?? null;

        if ($type === 'checkout.session.completed') {
            $this->fulfillCheckoutSession($event->data->object);
            return;
        }

        if ($type === 'checkout.session.expired') {
            $this->markPurchaseStatusFromObject($event->data->object, 'canceled');
            return;
        }

        if ($type === 'payment_intent.payment_failed') {
            $this->markPurchaseStatusFromObject($event->data->object, 'failed');
            return;
        }
    }

    public function markPurchaseStatusFromObject(object $object, string $status): void
    {
        $metadata = (array) ($object->metadata ?? []);
        $purchaseId = $metadata['purchase_id'] ?? null;

        $purchase = null;

        if ($purchaseId) {
            $purchase = UserBuyBook::find($purchaseId);
        }

        if (!$purchase && !empty($object->id)) {
            $purchase = UserBuyBook::where('stripe_checkout_session_id', $object->id)
                ->orWhere('stripe_payment_intent_id', $object->id)
                ->first();
        }

        if (!$purchase && !empty($object->payment_intent)) {
            $purchase = UserBuyBook::where('stripe_payment_intent_id', $object->payment_intent)->first();
        }

        if (!$purchase || $purchase->status === 'paid') {
            return;
        }

        $purchase->update(['status' => $status]);

        $purchase = $purchase->fresh();
        event(new PurchaseStatusUpdated($purchase));
        \App\Http\Controllers\Api\DashboardOverviewController::broadcastStats();
    }

    public function fulfillCheckoutSession(object $session): void
    {
        $metadata = (array) ($session->metadata ?? []);
        $type = $metadata['type'] ?? null;

        if ($type === 'book_purchase') {
            $purchaseId = $metadata['purchase_id'] ?? null;
            if (!$purchaseId) {
                return;
            }

            $purchase = UserBuyBook::find($purchaseId);
            if (!$purchase) {
                return;
            }

            $purchase->update([
                'status' => 'paid',
                'purchased_at' => now(),
                'stripe_checkout_session_id' => $session->id ?? null,
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
            ]);

            $purchase = PurchaseCommission::applyToPurchase($purchase->fresh());
            event(new PurchaseStatusUpdated($purchase));
            \App\Http\Controllers\Api\DashboardOverviewController::broadcastStats();

            return;
        }

        if ($type === 'subscription') {
            $userId = $metadata['user_id'] ?? null;
            if (!$userId) {
                return;
            }

            $user = User::find($userId);
            if ($user) {
                $user->update(['user_subscribe' => true]);
            }
        }
    }

    private function initStripe(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
}
