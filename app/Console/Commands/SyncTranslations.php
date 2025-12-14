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

#[AsCommand(name: 'translations:sync', description: 'Reconcile Koha PO files with saved translations.')]
class SyncTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:sync {--path= : Directory containing Koha PO files (defaults to storage/po)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile Koha PO files with saved translations.';

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
            $this->warn('No .po files found to sync.');

            return Command::SUCCESS;
        }

        $added = 0;
        $matched = 0;

        $loader = new PoLoader();

        DB::transaction(function () use ($poFiles, $directory, &$added, &$matched, $loader) {
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
                    $checksum = Translation::checksumFor($msgid, $context);

                    $model = Translation::firstOrNew(['checksum' => $checksum]);
                    $model->file_path = $relativePath;
                    $model->msgid = $msgid;
                    $model->context = $context;

                    if (! $model->exists) {
                        $model->msgstr = $this->extractTranslation($entry);
                        $model->save();
                        $added++;
                        continue;
                    }

                    $desiredTranslation = blank($model->msgstr)
                        ? $this->extractTranslation($entry)
                        : $model->msgstr;

                    if ($desiredTranslation !== null) {
                        $this->applyTranslation($entry, $desiredTranslation);
                        $model->msgstr = $desiredTranslation;
                    }

                    $model->save();
                    $matched++;
                }
            }
        });

        $this->info("Synced {$matched} existing translations; added {$added} new source strings.");

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

    /**
     * Apply a stored translation back onto the PO entry.
     */
    private function applyTranslation(PoEntry $entry, string $translation): void
    {
        if ($entry->getPlural() !== null) {
            $parts = explode(PHP_EOL, $translation);
            $entry->translate(array_shift($parts) ?? '');

            if (count($parts) > 0) {
                $entry->translatePlural(...$parts);
            }

            return;
        }

        $entry->translate($translation);
    }
}
