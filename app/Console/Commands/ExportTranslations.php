<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Translation as PoEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'translations:export', description: 'Export translated PO files ready for deployment.')]
class ExportTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:export {--source= : Directory containing Koha PO files (defaults to storage/po)} {--target= : Directory for exported files (defaults to storage/exports)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export translated PO files ready for deployment.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceDirectory = $this->option('source') ?: storage_path('po');
        $targetDirectory = $this->option('target') ?: storage_path('exports');

        $sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR);
        $targetDirectory = rtrim($targetDirectory, DIRECTORY_SEPARATOR);

        if (! File::isDirectory($sourceDirectory)) {
            $this->error("Source directory not found: {$sourceDirectory}");

            return Command::FAILURE;
        }

        File::ensureDirectoryExists($targetDirectory);

        $poFiles = collect(File::allFiles($sourceDirectory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'po')
            ->values();

        if ($poFiles->isEmpty()) {
            $this->warn('No .po files found to export.');

            return Command::SUCCESS;
        }

        $translations = Translation::query()
            ->whereNotNull('checksum')
            ->get()
            ->keyBy('checksum');

        $exported = 0;

        $loader = new PoLoader();

        $generator = new PoGenerator();

        foreach ($poFiles as $file) {
            $relativePath = Str::of($file->getPathname())
                ->after($sourceDirectory . DIRECTORY_SEPARATOR)
                ->ltrim(DIRECTORY_SEPARATOR);
            $entries = $loader->loadFile($file->getPathname());

            foreach ($entries as $entry) {
                $msgid = $entry->getOriginal();
                if ($msgid === '') {
                    continue;
                }

                $context = $entry->getContext() ?: null;
                $checksum = Translation::checksumFor($msgid, $context);

                $record = $translations->get($checksum);
                if ($record === null) {
                    continue;
                }

                if (blank($record->msgstr)) {
                    continue;
                }

                $this->applyTranslation($entry, $record->msgstr);
            }

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $relativePath;
            File::ensureDirectoryExists(dirname($targetPath));
            $generator->generateFile($entries, $targetPath);
            $exported++;
        }

        $this->info("Exported {$exported} file(s) to {$targetDirectory}.");

        return Command::SUCCESS;
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
