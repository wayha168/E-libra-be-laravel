<?php

namespace App\Http\Controllers\View;

use App\Models\BankAccount;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BankAccountController
{
    public function index(Request $request): View
    {
        $owner = $this->resolveOwner($request);
        $accounts = BankAccount::where('user_id', $owner->id)->latest()->get();

        return view('dashboard.account.bank.index', compact('accounts', 'owner'));
    }

    public function create(Request $request): View
    {
        $owner = $this->resolveOwner($request);

        return view('dashboard.account.bank.create', compact('owner'));
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = $this->resolveOwner($request);
        $data = $this->validated($request);
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            BankAccount::where('user_id', $owner->id)->update(['is_default' => false]);
        }

        BankAccount::create([
            ...$data,
            'user_id' => $owner->id,
        ]);

        ActivityLogger::log(
            'bank.created',
            'Bank account added',
            "{$data['provider']} — {$data['account_holder']}",
            $owner,
            $request->user(),
            ['provider' => $data['provider']],
        );

        return redirect()->route('dashboard.account.bank.index', $this->ownerQuery($request))
            ->with('success', 'Bank account added.');
    }

    public function edit(Request $request, BankAccount $bankAccount): View
    {
        $this->authorizeAccount($request, $bankAccount);
        $owner = $bankAccount->user;

        return view('dashboard.account.bank.edit', compact('bankAccount', 'owner'));
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorizeAccount($request, $bankAccount);
        $data = $this->validated($request);
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            BankAccount::where('user_id', $bankAccount->user_id)
                ->where('id', '!=', $bankAccount->id)
                ->update(['is_default' => false]);
        }

        $bankAccount->update($data);

        ActivityLogger::log(
            'bank.updated',
            'Bank account updated',
            "{$bankAccount->providerLabel()} — {$bankAccount->account_holder}",
            $bankAccount->user,
            $request->user(),
            ['bank_account_id' => $bankAccount->id],
        );

        return redirect()->route('dashboard.account.bank.index', $this->ownerQuery($request))
            ->with('success', 'Bank account updated.');
    }

    public function destroy(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $this->authorizeAccount($request, $bankAccount);
        $owner = $bankAccount->user;
        $label = "{$bankAccount->providerLabel()} — {$bankAccount->account_holder}";
        $bankAccount->delete();

        ActivityLogger::log(
            'bank.deleted',
            'Bank account removed',
            $label,
            $owner,
            $request->user(),
            ['provider' => $bankAccount->provider],
        );

        return redirect()->route('dashboard.account.bank.index', $this->ownerQuery($request))
            ->with('success', 'Bank account deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'provider' => 'required|in:bank,payway,bakong',
            'bank_name' => 'nullable|string|max:255',
            'account_holder' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'branch' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
        ]);
    }

    private function resolveOwner(Request $request): User
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            $targetId = $request->string('user_id')->toString();
            if ($targetId) {
                return User::findOrFail($targetId);
            }
        }

        return $user;
    }

    private function authorizeAccount(Request $request, BankAccount $bankAccount): void
    {
        $user = $request->user();

        if ($bankAccount->user_id === $user->id) {
            return;
        }

        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return;
        }

        abort(403);
    }

    private function ownerQuery(Request $request): array
    {
        $user = $request->user();
        if (($user->isAdmin() || $user->isSuperAdmin()) && $request->filled('user_id')) {
            return ['user_id' => $request->string('user_id')->toString()];
        }

        return [];
    }
}
