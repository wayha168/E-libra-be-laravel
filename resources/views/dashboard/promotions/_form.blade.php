@php
    $promotion = $promotion ?? null;
    $type = old('type', $promotion->type ?? 'percentage');
    $scope = old('scope', ($promotion && $promotion->author_id && ! $promotion->book_id) ? 'author' : 'book');
    $selectedBook = old('book_id', $promotion->book_id ?? '');
    $selectedAuthor = old('author_id', $promotion->author_id ?? ($ownAuthorId ?? ''));
    $discount = old('discount_percent', $promotion->discount_percent ?? '');
    $trialDays = old('trial_days', $promotion->trial_days ?? 7);
    $startsAt = old('starts_at', optional($promotion->starts_at ?? null)->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', optional($promotion->ends_at ?? null)->format('Y-m-d\TH:i'));
    $isActive = old('is_active', $promotion->is_active ?? true);
    $isAuthorOnly = $isAuthorOnly ?? false;
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
    <label class="block text-sm text-gray-600 mb-1">Promotion type</label>
    <div class="flex flex-wrap gap-2">
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-white cursor-pointer">
            <input type="radio" name="type" value="percentage" class="accent-black" @checked($type === 'percentage') data-promo-type />
            <span class="text-sm font-medium">Percentage discount</span>
        </label>
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-white cursor-pointer">
            <input type="radio" name="type" value="free_trial" class="accent-black" @checked($type === 'free_trial') data-promo-type />
            <span class="text-sm font-medium">Free trial (7 days)</span>
        </label>
    </div>
    <p class="text-xs text-gray-500 mt-1">Free trial starts when a user logs in and requests access to the book.</p>
</div>

<div>
    <label class="block text-sm text-gray-600 mb-1">Apply to</label>
    <div class="flex flex-wrap gap-2">
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-white cursor-pointer">
            <input type="radio" name="scope" value="book" class="accent-black" @checked($scope === 'book') data-promo-scope />
            <span class="text-sm font-medium">Single book</span>
        </label>
        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 bg-white cursor-pointer">
            <input type="radio" name="scope" value="author" class="accent-black" @checked($scope === 'author') data-promo-scope />
            <span class="text-sm font-medium">All books by author</span>
        </label>
    </div>
</div>

<div id="bookScopeFields">
    <label class="block text-sm text-gray-600 mb-1">Book</label>
    <select name="book_id" id="promoBookId" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
        <option value="">Select a book…</option>
        @foreach($books as $book)
        <option value="{{ $book->id }}" @selected($selectedBook === $book->id)>
            {{ $book->title }} (${{ number_format((float) $book->price, 2) }})
        </option>
        @endforeach
    </select>
    <p class="text-xs text-gray-500 mt-1">Only paid books can have a promotion.</p>
</div>

<div id="authorScopeFields" class="hidden">
    <label class="block text-sm text-gray-600 mb-1">Author</label>
    @if($isAuthorOnly)
        <input type="hidden" name="author_id" value="{{ $ownAuthorId }}" />
        <div class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-gray-50 text-sm">
            {{ optional(optional($authors->first())->user)->name ?? 'Your author profile' }} — all your books
        </div>
    @else
        <select name="author_id" id="promoAuthorId" class="w-full border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40">
            <option value="">Select an author…</option>
            @foreach($authors as $author)
            <option value="{{ $author->id }}" @selected((string) $selectedAuthor === (string) $author->id)>
                {{ $author->user->name ?? 'Author' }}
            </option>
            @endforeach
        </select>
    @endif
    <p class="text-xs text-gray-500 mt-1">Every paid book belonging to this author gets the promotion.</p>
</div>

<div id="percentFields">
    <label class="block text-sm text-gray-600 mb-1">Discount percent</label>
    <input name="discount_percent" id="promoDiscount" type="number" min="1" max="90" value="{{ $discount }}" class="w-full md:w-1/3 border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
    <p class="text-xs text-gray-500 mt-1">Between 1% and 90%.</p>
</div>

<div id="trialFields" class="hidden">
    <label class="block text-sm text-gray-600 mb-1">Trial days</label>
    <input name="trial_days" id="promoTrialDays" type="number" min="1" max="30" value="{{ $trialDays }}" class="w-full md:w-1/3 border border-gray-300 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-gray-900/10 focus:border-gray-900/40" />
    <p class="text-xs text-gray-500 mt-1">Counted from the moment the user requests access. After this, payment is required.</p>
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

<label class="inline-flex items-center gap-2.5 text-sm text-gray-700 cursor-pointer select-none">
    <input type="hidden" name="is_active" value="0" />
    <input type="checkbox" name="is_active" value="1" @checked($isActive) class="w-4 h-4 rounded border-gray-300 accent-black" />
    Active
</label>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.currentScript?.closest('form') || document.querySelector('form');
    if (!form) return;

    const bookFields = document.getElementById('bookScopeFields');
    const authorFields = document.getElementById('authorScopeFields');
    const percentFields = document.getElementById('percentFields');
    const trialFields = document.getElementById('trialFields');
    const bookSelect = document.getElementById('promoBookId');
    const discountInput = document.getElementById('promoDiscount');
    const trialInput = document.getElementById('promoTrialDays');

    function sync() {
        const type = form.querySelector('input[name="type"]:checked')?.value || 'percentage';
        const scope = form.querySelector('input[name="scope"]:checked')?.value || 'book';
        const isBook = scope === 'book';
        const isPercent = type === 'percentage';

        bookFields.classList.toggle('hidden', !isBook);
        authorFields.classList.toggle('hidden', isBook);
        percentFields.classList.toggle('hidden', !isPercent);
        trialFields.classList.toggle('hidden', isPercent);

        if (bookSelect) {
            bookSelect.required = isBook;
            bookSelect.disabled = !isBook;
        }
        if (discountInput) {
            discountInput.required = isPercent;
            discountInput.disabled = !isPercent;
        }
        if (trialInput) {
            trialInput.required = !isPercent;
            trialInput.disabled = isPercent;
        }
    }

    form.querySelectorAll('[data-promo-type], [data-promo-scope]').forEach((el) => {
        el.addEventListener('change', sync);
    });
    sync();
});
</script>
