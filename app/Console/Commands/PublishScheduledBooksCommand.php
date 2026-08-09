<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Support\BookPublishService;
use Illuminate\Console\Command;

class PublishScheduledBooksCommand extends Command
{
    protected $signature = 'books:publish-scheduled';

    protected $description = 'Publish books whose scheduled_at time has arrived';

    public function handle(): int
    {
        $query = Books::query()
            ->where('status', BookPublishService::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());

        $count = 0;

        $query->orderBy('scheduled_at')->chunkById(50, function ($books) use (&$count) {
            foreach ($books as $book) {
                BookPublishService::publishNow($book, notify: true);
                $count++;
                $this->line("Published: {$book->title} ({$book->id})");
            }
        });

        $this->info("Published {$count} scheduled book(s).");

        return self::SUCCESS;
    }
}
