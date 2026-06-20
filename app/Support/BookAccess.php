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
        return !is_null($book->price) && $book->price > 0;
    }

    public static function hasPdf(Books $book): bool
    {
        return BookPdfStorage::resolveFullPath($book) !== null;
    }

    public static function canAccessFull($user, Books $book): bool
    {
        if (!self::isPaid($book)) {
            return true;
        }

        if (!$user) {
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
        $book->can_preview = $paid && $hasPdf && !$fullAccess;
        $book->trial_pages = self::trialPages();

        $discount = BookPricing::discountMeta($book);
        $book->original_price = $discount['original_price'];
        $book->effective_price = $discount['effective_price'];
        $book->discount_percent = $discount['discount_percent'];
        $book->on_sale = $discount['on_sale'];

        $onTrial = $user && method_exists($user, 'onTrial') && $user->onTrial();
        $book->on_trial = (bool) $onTrial;
        $book->trial_ends_at = $onTrial ? $user->trial_ends_at : null;
        $book->preview_url = ($paid && $hasPdf) ? url('/api/v1/books/' . $book->id . '/preview') : null;
        $book->download_url = ($hasPdf && $fullAccess) ? url('/api/v1/books/' . $book->id . '/download') : null;
        $book->read_url = $hasPdf ? url('/dashboard/books/' . $book->id . '/read') : null;

        unset($book->pdf_file, $book->pdf_preview_path);
    }
}
