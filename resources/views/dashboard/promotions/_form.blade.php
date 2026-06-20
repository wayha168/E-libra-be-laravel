@php
    $promotion = $promotion ?? null;
    $selectedBook = old('book_id', $promotion->book_id ?? '');
    $discount = old('discount_percent', $promotion->discount_percent ?? '');
    $startsAt = old('starts_at', optional($promotion->starts_at ?? null)->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', optional($promotion->ends_at ?? null)->format('Y-m-d\TH:i'));
    $isActive = old('is_active', $promotion->is_active ?? true);
@endphp

@if($errors->any())
<div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">
    <ul class="list-disc pl-5">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div>
    <label class="block text-sm text-gray-600 mb-1">Book</label>
    <select name="book_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
        <option value="">Select a book…</option>
        @foreach($books as $book)
        <option value="{{ $book->id }}" @selected($selectedBook === $book->id)>
            {{ $book->title }} (${{ number_format((float) $book->price, 2) }})
        </option>
        @endforeach
    </select>
    <p class="text-xs text-gray-500 mt-1">Only paid books can have a promotion.</p>
</div>

<div>
    <label class="block text-sm text-gray-600 mb-1">Discount percent</label>
    <input name="discount_percent" type="number" min="1" max="90" value="{{ $discount }}" required class="w-full md:w-1/3 border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
    <p class="text-xs text-gray-500 mt-1">Between 1% and 90%.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm text-gray-600 mb-1">Starts at (optional)</label>
        <input name="starts_at" type="datetime-local" value="{{ $startsAt }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
    </div>
    <div>
        <label class="block text-sm text-gray-600 mb-1">Ends at (optional)</label>
        <input name="ends_at" type="datetime-local" value="{{ $endsAt }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
    </div>
</div>

<label class="flex items-center gap-2 text-sm text-gray-700">
    <input type="hidden" name="is_active" value="0" />
    <input type="checkbox" name="is_active" value="1" @checked($isActive) class="rounded border-gray-300" />
    Active
</label>
