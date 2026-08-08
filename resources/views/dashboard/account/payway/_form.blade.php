@props(['merchant' => null, 'owners', 'prefillUserId' => null])

@php
    $selectedUserId = old('user_id', $merchant?->user_id ?? $prefillUserId);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Owner (author / admin)</label>
        <select name="user_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <option value="">Select user…</option>
            @foreach($owners as $owner)
            <option value="{{ $owner->id }}" @selected((string) $selectedUserId === (string) $owner->id)>
                {{ $owner->name }} ({{ $owner->email }}) — {{ $owner->display_role }}
            </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Merchant ID</label>
        <input name="merchant_id" value="{{ old('merchant_id', $merchant?->merchant_id) }}" required maxlength="30" class="w-full border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm" placeholder="Provided by ABA PayWay" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
        <input name="api_key" type="password" value="{{ old('api_key') }}" @required(!$merchant) autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 font-mono text-sm" placeholder="{{ $merchant ? 'Leave blank to keep current key' : 'API key from ABA PayWay' }}" />
        @if($merchant)
        <p class="mt-1 text-xs text-gray-500">Current: {{ $merchant->maskedApiKey() }}</p>
        @endif
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Display name</label>
        <input name="merchant_name" value="{{ old('merchant_name', $merchant?->merchant_name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Optional label" />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Environment</label>
        <select name="environment" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @foreach(['sandbox' => 'Sandbox', 'production' => 'Production'] as $value => $label)
            <option value="{{ $value }}" @selected(old('environment', $merchant?->environment ?? 'sandbox') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
        <select name="currency" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @foreach(['USD', 'KHR'] as $currency)
            <option value="{{ $currency }}" @selected(old('currency', $merchant?->currency ?? 'USD') === $currency)>{{ $currency }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Default payment option</label>
        <select name="payment_option" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @foreach([
                '' => 'All enabled methods',
                'cards' => 'Cards',
                'abapay_khqr' => 'ABA Pay / KHQR',
                'abapay_khqr_deeplink' => 'ABA Pay / KHQR (deeplink)',
                'alipay' => 'Alipay',
                'wechat' => 'WeChat',
                'google_pay' => 'Google Pay',
            ] as $value => $label)
            <option value="{{ $value }}" @selected((string) old('payment_option', $merchant?->payment_option ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="is_active" value="0" />
        <input type="checkbox" name="is_active" value="1" id="is_active" class="rounded border-gray-300" @checked(old('is_active', $merchant?->is_active ?? true)) />
        <label for="is_active" class="text-sm text-gray-700">Active</label>
    </div>

    <div class="flex items-center gap-2 pt-6">
        <input type="hidden" name="is_platform" value="0" />
        <input type="checkbox" name="is_platform" value="1" id="is_platform" class="rounded border-gray-300" @checked(old('is_platform', $merchant?->is_platform ?? false)) />
        <label for="is_platform" class="text-sm text-gray-700">Company / platform account (DB fallback for all books)</label>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Optional internal notes">{{ old('notes', $merchant?->notes) }}</textarea>
    </div>
</div>
