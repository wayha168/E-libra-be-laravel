@props(['account' => null])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
        <select name="provider" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            @foreach(['bank' => 'Bank', 'payway' => 'PayWay', 'bakong' => 'Bakong'] as $value => $label)
            <option value="{{ $value }}" @selected(old('provider', $account?->provider ?? 'bank') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Bank name</label>
        <input name="bank_name" value="{{ old('bank_name', $account?->bank_name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Optional for PayWay/Bakong" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Account holder</label>
        <input name="account_holder" value="{{ old('account_holder', $account?->account_holder) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Account number</label>
        <input name="account_number" value="{{ old('account_number', $account?->account_number) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2" />
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
        <input name="branch" value="{{ old('branch', $account?->branch) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
    </div>
    <div class="flex items-center gap-2 pt-6">
        <input type="checkbox" name="is_default" value="1" id="is_default" class="rounded border-gray-300" @checked(old('is_default', $account?->is_default)) />
        <label for="is_default" class="text-sm text-gray-700">Set as default payout account</label>
    </div>
</div>
