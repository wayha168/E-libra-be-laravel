<?php

namespace App\Http\Controllers\View;

use App\Models\AbaPaywayMerchant;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AbaPaywayMerchantController
{
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin($request);

        $merchants = AbaPaywayMerchant::with('user.role')
            ->latest()
            ->get();

        return view('dashboard.account.payway.index', compact('merchants'));
    }

    public function create(Request $request): View
    {
        $this->ensureSuperAdmin($request);

        $owners = $this->eligibleOwners();
        $prefillUserId = $request->string('user_id')->toString() ?: null;

        return view('dashboard.account.payway.create', compact('owners', 'prefillUserId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $data = $this->validated($request);

        $owner = User::findOrFail($data['user_id']);
        $this->assertEligibleOwner($owner);

        if (AbaPaywayMerchant::where('user_id', $owner->id)->exists()) {
            return redirect()
                ->route('dashboard.account.payway.create', ['user_id' => $owner->id])
                ->withInput()
                ->with('error', 'This user already has ABA PayWay credentials. Edit the existing record instead.');
        }

        $merchant = AbaPaywayMerchant::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'is_platform' => $request->boolean('is_platform', false),
            'payment_option' => $data['payment_option'] ?: 'abapay_khqr',
            'notes' => $data['notes'] ?: null,
            'merchant_name' => $data['merchant_name'] ?: null,
        ]);

        if ($merchant->is_platform) {
            AbaPaywayMerchant::query()
                ->where('id', '!=', $merchant->id)
                ->where('is_platform', true)
                ->update(['is_platform' => false]);
        }

        ActivityLogger::log(
            'payway.created',
            'ABA PayWay merchant configured',
            "{$merchant->merchant_id} for {$owner->name}",
            $owner,
            $request->user(),
            ['merchant_id' => $merchant->merchant_id, 'environment' => $merchant->environment],
        );

        return redirect()
            ->route('dashboard.account.payway.index')
            ->with('success', 'ABA PayWay credentials saved for ' . $owner->name . '.');
    }

    public function edit(Request $request, AbaPaywayMerchant $payway): View
    {
        $this->ensureSuperAdmin($request);
        $payway->load('user.role');
        $owners = $this->eligibleOwners();

        return view('dashboard.account.payway.edit', [
            'merchant' => $payway,
            'owners' => $owners,
        ]);
    }

    public function update(Request $request, AbaPaywayMerchant $payway): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
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
            'is_active' => $request->boolean('is_active'),
            'is_platform' => $request->boolean('is_platform'),
            'notes' => $data['notes'] ?: null,
        ];

        if (filled($data['api_key'] ?? null)) {
            $payload['api_key'] = $data['api_key'];
        }

        $payway->update($payload);

        if ($payway->is_platform) {
            AbaPaywayMerchant::query()
                ->where('id', '!=', $payway->id)
                ->where('is_platform', true)
                ->update(['is_platform' => false]);
        }

        ActivityLogger::log(
            'payway.updated',
            'ABA PayWay merchant updated',
            "{$payway->merchant_id} for {$owner->name}",
            $owner,
            $request->user(),
            ['merchant_id' => $payway->merchant_id, 'environment' => $payway->environment],
        );

        return redirect()
            ->route('dashboard.account.payway.index')
            ->with('success', 'ABA PayWay credentials updated.');
    }

    public function destroy(Request $request, AbaPaywayMerchant $payway): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
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

        return redirect()
            ->route('dashboard.account.payway.index')
            ->with('success', 'ABA PayWay credentials deleted.');
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
            'is_platform' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function eligibleOwners()
    {
        return User::query()
            ->with('role')
            ->whereHas('role', fn ($q) => $q->whereIn('role', ['super_admin', 'admin', 'author']))
            ->orderBy('name')
            ->get();
    }

    private function assertEligibleOwner(User $owner): void
    {
        if (!($owner->isSuperAdmin() || $owner->isAdmin() || $owner->isAuthor())) {
            abort(422, 'ABA PayWay credentials can only be assigned to authors or admins.');
        }
    }

    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403);
        }
    }
}
