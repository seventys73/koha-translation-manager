<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Gettext\Loader\PoLoader;
use Gettext\Translation as PoEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'translations:import', description: 'Import Koha PO files into the translations database.')]
class ImportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:import {--path= : Directory containing Koha PO files (defaults to storage/po)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Koha PO files into the translations database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directory = $this->option('path') ?: storage_path('po');
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if (! File::isDirectory($directory)) {
            $this->error("Directory not found: {$directory}");

            return Command::FAILURE;
        }

        $poFiles = collect(File::allFiles($directory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'po')
            ->values();

        if ($poFiles->isEmpty()) {
            $this->warn('No .po files found to import.');

            return Command::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        $loader = new PoLoader();

        DB::transaction(function () use ($poFiles, $directory, &$created, &$updated, $loader) {
            foreach ($poFiles as $file) {
                $relativePath = Str::of($file->getPathname())
                    ->after($directory . DIRECTORY_SEPARATOR)
                    ->ltrim(DIRECTORY_SEPARATOR);

                $entries = $loader->loadFile($file->getPathname());
                foreach ($entries as $entry) {
                    $msgid = $entry->getOriginal();
                    if ($msgid === '') {
                        continue;
                    }

                    $context = $entry->getContext() ?: null;
                    $msgstr = $this->extractTranslation($entry);
                    $checksum = Translation::checksumFor($msgid, $context);

                    $model = Translation::firstOrNew(['checksum' => $checksum]);
                    $model->file_path = $relativePath;
                    $model->msgid = $msgid;
                    $model->context = $context;

                    if (! $model->exists) {
                        $model->msgstr = $msgstr;
                    } elseif ($msgstr !== null && blank($model->msgstr)) {
                        $model->msgstr = $msgstr;
                    }

                    if ($model->exists) {
                        $updated++;
                    } else {
                        $created++;
                    }

                    $model->save();
                }
            }
        });

        $this->info("Imported {$created} new translations, updated {$updated} existing.");

        return Command::SUCCESS;
    }

    /**
     * Extract a normalized translation string from a PO entry.
     */
    private function extractTranslation(PoEntry $entry): ?string
    {
        $value = $entry->getTranslation();
        $pluralParts = array_filter($entry->getPluralTranslations() ?: [], fn ($part) => $part !== null && $part !== '');

        if ($entry->getPlural() !== null) {
            $parts = [];
            if ($value !== null && $value !== '') {
                $parts[] = $value;
            }
            foreach ($pluralParts as $part) {
                $parts[] = $part;
            }

            return count($parts) > 0 ? implode(PHP_EOL, $parts) : null;
        }

        return $value !== null && $value !== '' ? $value : null;
    }
}
