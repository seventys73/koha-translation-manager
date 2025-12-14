<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class DashboardController extends Controller
{
    public const DEFAULT_KOHA_PO_PATH = '/usr/share/koha/misc/translator/po';

    /**
     * Display the dashboard with tooling shortcuts.
     */
    public function index()
    {
        $poDirectory = storage_path('po');
        File::ensureDirectoryExists($poDirectory);

        $poFiles = collect(File::files($poDirectory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $this->humanFileSize($file->getSize()),
                'updated' => Carbon::createFromTimestamp($file->getMTime())->diffForHumans(),
            ])
            ->values();

        return view('dashboard', [
            'poFiles' => $poFiles,
            'importSummary' => session('import_status'),
            'syncReport' => session('sync_report'),
            'remoteImportSummary' => session('remote_import_status'),
            'pushStatus' => session('push_status'),
            'kohaPath' => $this->kohaPath(),
            'targetLanguage' => $this->targetLanguage(),
        ]);
    }

    /**
     * Handle uploading PO files into the storage directory.
     */
    public function importLocal(Request $request)
    {
        $validated = $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:5120'],
        ]);

        $uploaded = $validated['files'];
        $poDirectory = storage_path('po');
        File::ensureDirectoryExists($poDirectory);

        $count = 0;
        foreach ($uploaded as $file) {
            $filename = $file->getClientOriginalName() ?: 'translation-' . Str::random(8) . '.po';
            $file->move($poDirectory, $filename);
            $count++;
        }

        return back()->with('import_status', "Uploaded {$count} file(s) successfully.");
    }

    /**
     * Copy PO files directly from the Koha installation directory.
     */
    public function importFromKoha()
    {
        $sourceDirectory = $this->kohaPath();
        $targetLanguage = $this->targetLanguage();

        if ($targetLanguage === null || trim($targetLanguage) === '') {
            return back()->withErrors(['import' => 'Target language is not configured. Please set it in Settings.']);
        }

        if (! File::isDirectory($sourceDirectory)) {
            return back()->withErrors(['import' => "Koha directory not found: {$sourceDirectory}"]);
        }

        $targetDirectory = storage_path('po');
        File::ensureDirectoryExists($targetDirectory);

        $needle = Str::lower($targetLanguage);

        $files = collect(File::allFiles($sourceDirectory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'po')
            ->filter(fn ($file) => Str::contains(Str::lower($file->getFilename()), $needle));

        if ($files->isEmpty()) {
            return back()->with('remote_import_status', 'No .po files found in Koha directory.');
        }

        $copied = 0;
        foreach ($files as $file) {
            $relative = Str::of($file->getPathname())
                ->after($sourceDirectory . DIRECTORY_SEPARATOR)
                ->ltrim(DIRECTORY_SEPARATOR);

            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $relative;
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
            $copied++;
        }

        return back()->with('remote_import_status', "Copied {$copied} file(s) from Koha directory.");
    }

    /**
     * Run the sync command and return the report.
     */
    public function syncTranslations()
    {
        Artisan::call('translations:sync');
        $report = trim(Artisan::output()) ?: 'Sync completed.';

        return back()->with('sync_report', $report);
    }

    /**
     * Run export and return a ZIP archive containing all PO files.
     */
    public function exportTranslations()
    {
        Artisan::call('translations:export');

        $exportsDirectory = storage_path('exports');
        File::ensureDirectoryExists($exportsDirectory);

        $zipName = 'koha-translations-' . now()->format('Ymd-His') . '.zip';
        $zipPath = storage_path('app/' . $zipName);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['export' => 'Unable to create ZIP archive.']);
        }

        $files = File::allFiles($exportsDirectory);
        foreach ($files as $file) {
            $relative = Str::of($file->getPathname())
                ->after($exportsDirectory . DIRECTORY_SEPARATOR)
                ->ltrim(DIRECTORY_SEPARATOR);

            $zip->addFile($file->getPathname(), $relative === '' ? $file->getFilename() : $relative);
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    /**
     * Push exported PO files back into the Koha directory.
     */
    public function pushToKoha()
    {
        $kohaDirectory = $this->kohaPath();

        if (! File::isDirectory($kohaDirectory)) {
            return back()->withErrors(['push' => "Koha directory not found: {$kohaDirectory}"]);
        }

        Artisan::call('translations:export');

        $exportsDirectory = storage_path('exports');
        File::ensureDirectoryExists($exportsDirectory);

        $files = collect(File::allFiles($exportsDirectory))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'po');

        if ($files->isEmpty()) {
            return back()->with('push_status', 'No exported .po files available to push.');
        }

        $generatorSummary = trim(Artisan::output()) ?: null;
        $pushed = 0;

        foreach ($files as $file) {
            $relative = Str::of($file->getPathname())
                ->after($exportsDirectory . DIRECTORY_SEPARATOR)
                ->ltrim(DIRECTORY_SEPARATOR);

            $targetPath = $kohaDirectory . DIRECTORY_SEPARATOR . $relative;
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($file->getPathname(), $targetPath);
            $pushed++;
        }

        $message = "Pushed {$pushed} file(s) to Koha directory.";
        if ($generatorSummary) {
            $message .= ' Export log: ' . $generatorSummary;
        }

        return back()->with('push_status', $message);
    }

    private function humanFileSize(int $bytes, int $decimals = 1): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$decimals}f %s", $bytes / (1024 ** $factor), $units[$factor - 1]);
    }

    private function kohaPath(): string
    {
        return Setting::get('koha_path', self::DEFAULT_KOHA_PO_PATH);
    }

    private function targetLanguage(): ?string
    {
        return Setting::get('target_language');
    }
}
