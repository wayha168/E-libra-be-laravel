<?php

namespace App\Console\Commands;

use App\Models\Books;
use App\Support\BookPdfStorage;
use App\Support\BookReadFile;
use Illuminate\Console\Command;

class GenerateBookReadFiles extends Command
{
    protected $signature = 'books:generate-read-files
        {--force : Regenerate even when a read copy already exists}
        {--book= : Limit to a single book id}';

    protected $description = 'Generate the compact DB-stored read copy of book PDFs for offline reading';

    public function handle(): int
    {
        $query = Books::query()->whereNotNull('pdf_file');

        if ($bookId = $this->option('book')) {
            $query->where('id', $bookId);
        }

        if (! $this->option('force')) {
            $query->whereNull('pdf_read_data');
        }

        $processed = 0;
        $generated = 0;
        $skipped = 0;

        $query->orderBy('created_at')->chunkById(50, function ($books) use (&$processed, &$generated, &$skipped) {
            foreach ($books as $book) {
                $processed++;

                $source = BookPdfStorage::resolveFullPath($book);
                if (! $source) {
                    $skipped++;
                    $this->warn("Skipped {$book->id}: source PDF not found.");

                    continue;
                }

                $readFile = BookReadFile::generate($source);
                if (! $readFile) {
                    $skipped++;
                    $this->warn("Skipped {$book->id}: could not build a read copy within the size cap.");

                    continue;
                }

                $book->forceFill([
                    'pdf_read_data' => $readFile['data'],
                    'pdf_read_size' => $readFile['size'],
                ])->save();

                $generated++;
                $this->info("Generated read copy for {$book->id} ({$readFile['size']} bytes).");
            }
        });

        $this->line("Done. Processed {$processed}, generated {$generated}, skipped {$skipped}.");

        return self::SUCCESS;
    }
}
