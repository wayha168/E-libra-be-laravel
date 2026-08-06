<?php

namespace App\Support;

use App\Models\Books;
use App\Models\UserBuyBook;

class BookAccess
{
    public static function trialPages(): int
    {
        return (int) config('elibra.book_trial_pages', 15);
    }

    public static function isPaid(Books $book): bool
    {
        return ! is_null($book->price) && $book->price > 0;
    }

    public static function hasPdf(Books $book): bool
    {
        return BookPdfStorage::resolveFullPath($book) !== null;
    }

    public static function canAccessFull($user, Books $book): bool
    {
        if (! self::isPaid($book)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if ($book->author_id && method_exists($user, 'authorProfile') && $user->authorProfile && $user->authorProfile->id === $book->author_id) {
            return true;
        }

        if ($user->user_subscribe ?? false) {
            return true;
        }

        if (method_exists($user, 'onTrial') && $user->onTrial()) {
            return true;
        }

        if (BookTrialAccess::hasActiveTrial($user, $book)) {
            return true;
        }

        return UserBuyBook::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'paid')
            ->exists();
    }

    public static function canPreview(Books $book): bool
    {
        return self::isPaid($book) && self::hasPdf($book);
    }

    public static function appendAccessMeta(Books $book, $user): void
    {
        $hasPdf = self::hasPdf($book);
        $fullAccess = self::canAccessFull($user, $book);
        $paid = self::isPaid($book);

        $book->has_pdf = $hasPdf;
        $book->has_full_access = $fullAccess;
        $book->can_preview = $paid && $hasPdf && ! $fullAccess;
        $book->trial_pages = self::trialPages();

        $discount = BookPricing::discountMeta($book);
        $book->original_price = $discount['original_price'];
        $book->effective_price = $discount['effective_price'];
        $book->discount_percent = $discount['discount_percent'];
        $book->on_sale = $discount['on_sale'];
        $book->promotion_scope = $discount['promotion_scope'];

        $promoTrial = $user ? BookTrialAccess::activeFreeTrialPromotion($book) : null;
        $claimed = $user ? BookTrialAccess::trialFor($user, $book) : null;
        $trialActive = $claimed?->isActive() === true;
        $trialExpired = $claimed?->isExpired() === true;

        $legacyTrial = $user && method_exists($user, 'onTrial') && $user->onTrial();

        $book->free_trial_available = (bool) $promoTrial && ! $claimed;
        $book->on_trial = $trialActive || (bool) $legacyTrial;
        $book->trial_ends_at = $trialActive
            ? $claimed->ends_at
            : ($legacyTrial ? $user->trial_ends_at : null);
        $book->trial_expired = $trialExpired && ! $fullAccess;
        $book->payment_required = $paid && ! $fullAccess && ($trialExpired || ! $promoTrial);
        $book->request_access_url = ($paid && $user)
            ? url('/api/v1/books/' . $book->id . '/request-access')
            : null;
        $book->buy_url = $paid ? url('/api/v1/books/' . $book->id . '/buy') : null;

        $book->preview_url = ($paid && $hasPdf) ? url('/api/v1/books/' . $book->id . '/preview') : null;
        $book->download_url = ($hasPdf && $fullAccess) ? url('/api/v1/books/' . $book->id . '/download') : null;
        // API read URL for the separate frontend (no dashboard login required)
        $book->read_url = ($hasPdf && $fullAccess)
            ? url('/api/v1/books/' . $book->id . '/download')
            : (($paid && $hasPdf) ? url('/api/v1/books/' . $book->id . '/preview') : null);

        unset($book->pdf_file, $book->pdf_preview_path);
    }
}
