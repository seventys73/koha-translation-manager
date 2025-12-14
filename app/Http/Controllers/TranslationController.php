<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    /**
     * Display a listing of translations with optional search filters.
     */
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $file = $request->string('file')->toString();
        $exact = $request->boolean('exact');

        $translations = Translation::query()
            ->when($file !== '', fn ($query) => $query->where('file_path', $file))
            ->when($search !== '', function ($query) use ($search, $exact) {
                $query->where(function ($inner) use ($search, $exact) {
                    if ($exact) {
                        $inner->where('msgid', $search)
                            ->orWhere('msgstr', $search)
                            ->orWhere('context', $search);
                    } else {
                        $inner->where('msgid', 'like', '%' . $search . '%')
                            ->orWhere('msgstr', 'like', '%' . $search . '%')
                            ->orWhere('context', 'like', '%' . $search . '%');
                    }
                });
            })
            ->orderBy('file_path')
            ->orderBy('msgid')
            ->paginate(perPage: 25)
            ->withQueryString();

        $availableFiles = Translation::query()
            ->select('file_path')
            ->distinct()
            ->orderBy('file_path')
            ->pluck('file_path');

        return view('translations.index', [
            'translations' => $translations,
            'search' => $search,
            'file' => $file,
            'exact' => $exact,
            'availableFiles' => $availableFiles,
        ]);
    }

    /**
     * Show the form for editing a specific translation.
     */
    public function edit(Translation $translation)
    {
        return view('translations.edit', compact('translation'));
    }

    /**
     * Persist updates to an existing translation.
     */
    public function update(Request $request, Translation $translation)
    {
        $data = $request->validate([
            'msgstr' => ['nullable', 'string'],
        ]);

        $translation->fill($data);
        $translation->checksum = Translation::checksumFor(
            $translation->msgid,
            $translation->context,
        );
        $translation->save();

        $redirectTo = $request->string('redirect_to')->toString();

        if ($redirectTo !== '') {
            return redirect($redirectTo)
                ->with('status', 'Translation updated successfully.')
                ->with('status_id', $translation->id);
        }

        return redirect()
            ->route('translations.edit', $translation)
            ->with('status', 'Translation updated successfully.');
    }
}
