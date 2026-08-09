<?php

return [
    'admin_commission_rate' => (float) env('ADMIN_COMMISSION_RATE', 10),
    // Free preview page count for paid books (recommended 10–20).
    'book_trial_pages' => max(1, min(50, (int) env('BOOK_TRIAL_PAGES', 15))),
    // Laravel file validation max is in kilobytes (524288 = 512 MB).
    'book_pdf_max_kb' => max(1024, (int) env('BOOK_PDF_MAX_KB', 524288)),
    'trial_days' => (int) env('TRIAL_DAYS', 7),
    // Allow Scramble UI at /docs/api outside local (set true on production if needed)
    'scramble_docs_enabled' => filter_var(env('SCRAMBLE_DOCS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
];
