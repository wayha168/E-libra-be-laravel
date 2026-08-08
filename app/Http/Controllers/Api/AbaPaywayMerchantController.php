<?php

namespace App\Http\Controllers\Api;

use App\Models\AbaPaywayMerchant;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AbaPaywayMerchantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $merchants = AbaPaywayMerchant::with('user:id,name,email')
            ->latest()
            ->paginate(20);

        $merchants->getCollection()->transform(fn (AbaPaywayMerchant $m) => $this->present($m));

        return response()->json([
            'message' => 'ABA PayWay merchants fetched successfully',
            'data' => $merchants,
        ]);
    }

    public function show(Request $request, AbaPaywayMerchant $payway): JsonResponse
    {
        $this->ensureAdmin($request);
        $payway->load('user:id,name,email');

        return response()->json([
            'message' => 'ABA PayWay merchant fetched successfully',
            'data' => $this->present($payway),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $this->validated($request);

        $owner = User::findOrFail($data['user_id']);
        $this->assertEligibleOwner($owner);

        if (AbaPaywayMerchant::where('user_id', $owner->id)->exists()) {
            return response()->json([
                'message' => 'This user already has ABA PayWay credentials.',
            ], 422);
        }

        $merchant = AbaPaywayMerchant::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'payment_option' => $data['payment_option'] ?: 'abapay_khqr',
            'notes' => $data['notes'] ?: null,
            'merchant_name' => $data['merchant_name'] ?: null,
        ]);

        ActivityLogger::log(
            'payway.created',
            'ABA PayWay merchant configured',
            "{$merchant->merchant_id} for {$owner->name}",
            $owner,
            $request->user(),
            ['merchant_id' => $merchant->merchant_id, 'environment' => $merchant->environment],
        );

        return response()->json([
            'message' => 'ABA PayWay merchant created successfully',
            'data' => $this->present($merchant->load('user:id,name,email')),
        ], 201);
    }

    public function update(Request $request, AbaPaywayMerchant $payway): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $this->validated($request, $payway);

        $owner = User::findOrFail($data['user_id']);
        $this->assertEligibleOwner($owner);

        $payload = [
            'user_id' => $owner->id,
            'merchant_id' => $data['merchant_id'],
            'merchant_name' => $data['merchant_name'] ?: null,
            'environment' => $data['environment'],
            'currency' => $data['currency'],
            'payment_option' => $data['payment_option'] ?: 'abapay_khqr',
            'is_active' => $request->boolean('is_active', $payway->is_active),
            'notes' => $data['notes'] ?: null,
        ];

        if (filled($data['api_key'] ?? null)) {
            $payload['api_key'] = $data['api_key'];
        }

        $payway->update($payload);

        ActivityLogger::log(
            'payway.updated',
            'ABA PayWay merchant updated',
            "{$payway->merchant_id} for {$owner->name}",
            $owner,
            $request->user(),
            ['merchant_id' => $payway->merchant_id, 'environment' => $payway->environment],
        );

        return response()->json([
            'message' => 'ABA PayWay merchant updated successfully',
            'data' => $this->present($payway->fresh()->load('user:id,name,email')),
        ]);
    }

    public function destroy(Request $request, AbaPaywayMerchant $payway): JsonResponse
    {
        $this->ensureAdmin($request);
        $owner = $payway->user;
        $label = "{$payway->merchant_id} — {$owner?->name}";
        $payway->delete();

        ActivityLogger::log(
            'payway.deleted',
            'ABA PayWay merchant removed',
            $label,
            $owner,
            $request->user(),
            ['merchant_id' => $payway->merchant_id],
        );

        return response()->json([
            'message' => 'ABA PayWay merchant deleted successfully',
            'data' => null,
        ]);
    }

    private function validated(Request $request, ?AbaPaywayMerchant $existing = null): array
    {
        $apiKeyRule = $existing
            ? ['nullable', 'string', 'max:2000']
            : ['required', 'string', 'max:2000'];

        return $request->validate([
            'user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id'),
                Rule::unique('aba_payway_merchants', 'user_id')->ignore($existing?->id),
            ],
            'merchant_id' => ['required', 'string', 'max:30'],
            'api_key' => $apiKeyRule,
            'merchant_name' => ['nullable', 'string', 'max:255'],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
            'currency' => ['required', Rule::in(['USD', 'KHR'])],
            'payment_option' => ['nullable', Rule::in([
                '',
                'cards',
                'abapay_khqr',
                'abapay_khqr_deeplink',
                'alipay',
                'wechat',
                'google_pay',
            ])],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function present(AbaPaywayMerchant $merchant): array
    {
        return [
            'id' => $merchant->id,
            'user_id' => $merchant->user_id,
            'user_name' => $merchant->user?->name,
            'user_email' => $merchant->user?->email,
            'merchant_id' => $merchant->merchant_id,
            'merchant_name' => $merchant->merchant_name,
            'api_key_masked' => $merchant->maskedApiKey(),
            'environment' => $merchant->environment,
            'currency' => $merchant->currency,
            'payment_option' => $merchant->payment_option ?: 'abapay_khqr',
            'payment_option_label' => $merchant->paymentOptionLabel(),
            'is_active' => (bool) $merchant->is_active,
            'notes' => $merchant->notes,
            'created_at' => $merchant->created_at?->toIso8601String(),
            'updated_at' => $merchant->updated_at?->toIso8601String(),
        ];
    }

    private function assertEligibleOwner(User $owner): void
    {
        if (! ($owner->isSuperAdmin() || $owner->isAdmin() || $owner->isAuthor())) {
            abort(422, 'ABA PayWay credentials can only be assigned to authors or admins.');
        }
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (! $user || ! ($user->isSuperAdmin() || $user->isAdmin())) {
            abort(403);
        }
    }
}
