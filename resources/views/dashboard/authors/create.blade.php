@extends('main')

@section('title', 'Create Author Account')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-4">
        <h1 class="text-2xl font-semibold">Create Author Account</h1>
        <p class="text-sm text-gray-600 mt-1">Admins can create a new login for an author, or link an existing user.</p>
    </div>

    @if($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('dashboard.authors.store') }}" class="space-y-5" enctype="multipart/form-data" id="authorCreateForm">
        @csrf

        <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-3 flex flex-wrap gap-2">
            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-gray-200 cursor-pointer has-[:checked]:border-black has-[:checked]:ring-2 has-[:checked]:ring-black/10">
                <input type="radio" name="mode" value="new_account" class="accent-black" {{ old('mode', 'new_account') === 'new_account' ? 'checked' : '' }} />
                <span class="text-sm font-medium">New author account</span>
            </label>
            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white border border-gray-200 cursor-pointer has-[:checked]:border-black has-[:checked]:ring-2 has-[:checked]:ring-black/10">
                <input type="radio" name="mode" value="existing_user" class="accent-black" {{ old('mode') === 'existing_user' ? 'checked' : '' }} />
                <span class="text-sm font-medium">Link existing user</span>
            </label>
        </div>

        <div id="newAccountFields" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Name</label>
                    <input name="name" value="{{ old('name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Password</label>
                    <input name="password" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Confirm Password</label>
                    <input name="password_confirmation" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Status</label>
                <input type="hidden" name="status" value="inactive" data-status-field />
                <label class="inline-flex items-center gap-2.5 mt-1 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        name="status"
                        value="active"
                        data-status-field
                        class="w-4 h-4 rounded border-gray-300 accent-black cursor-pointer"
                        {{ old('status', 'active') === 'active' ? 'checked' : '' }}
                    />
                    <span class="text-sm text-gray-800">Active</span>
                </label>
            </div>
        </div>

        <div id="existingUserFields" class="space-y-4 hidden">
            <div>
                <label class="block text-sm text-gray-600 mb-1">User (no author profile yet)</label>
                <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- Select user --</option>
                    @foreach($users ?? [] as $user)
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }} ({{ $user->email }}) — {{ $user->display_role }}
                    </option>
                    @endforeach
                </select>
                @if(($users ?? collect())->isEmpty())
                <p class="mt-1 text-xs text-gray-500">No eligible users without an author profile.</p>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Bio</label>
            <textarea name="bio" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" rows="3" placeholder="Short author bio…">{{ old('bio') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Upload photo (optional)</label>
                <input type="file" name="image_file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Or pick existing image</label>
                <select name="image_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
                    <option value="">-- No image --</option>
                    @foreach($images ?? [] as $image)
                    <option value="{{ $image->id }}" {{ old('image_id') == $image->id ? 'selected' : '' }}>{{ Str::limit($image->alt_text ?: $image->url, 40) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-2 pt-1">
            <a href="{{ route('dashboard.authors.index') }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Back</a>
            <button class="px-3 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition" type="submit">Create author</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('authorCreateForm');
    if (!form) return;

    const newFields = document.getElementById('newAccountFields');
    const existingFields = document.getElementById('existingUserFields');
    const radios = form.querySelectorAll('input[name="mode"]');

    function syncMode() {
        const mode = form.querySelector('input[name="mode"]:checked')?.value || 'new_account';
        const isNew = mode === 'new_account';
        newFields.classList.toggle('hidden', !isNew);
        existingFields.classList.toggle('hidden', isNew);

        newFields.querySelectorAll('input, select, textarea').forEach((el) => {
            if (el.name === 'name' || el.name === 'email' || el.name === 'password' || el.name === 'password_confirmation') {
                el.required = isNew;
                el.disabled = !isNew;
            }
        });

        newFields.querySelectorAll('[data-status-field]').forEach((el) => {
            el.disabled = !isNew;
        });

        const userSelect = existingFields.querySelector('select[name="user_id"]');
        if (userSelect) {
            userSelect.required = !isNew;
            userSelect.disabled = isNew;
        }
    }

    radios.forEach((r) => r.addEventListener('change', syncMode));
    syncMode();
});
</script>
@endsection
