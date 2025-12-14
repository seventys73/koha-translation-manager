<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">Translations</h1>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('translations.index') }}">Refresh</a>
        </div>
    </x-slot>

    <div class="container py-4">
        <form method="GET" action="{{ route('translations.index') }}" class="card card-body mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search text</label>
                    <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search msgid, msgstr, or context">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-4 pt-2">
                        <input class="form-check-input" type="checkbox" value="1" id="exact" name="exact" @checked($exact)>
                        <label class="form-check-label" for="exact">
                            Exact match only
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="file" class="form-label">Filter by file</label>
                    <select id="file" name="file" class="form-select">
                        <option value="">All files</option>
                        @foreach ($availableFiles as $filePath)
                            <option value="{{ $filePath }}" @selected($file === $filePath)>{{ $filePath }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-md-end">
                    <button type="submit" class="btn btn-primary w-100">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>

        @if (session('status') && ! session()->has('status_id'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">File</th>
                            <th scope="col">Context</th>
                            <th scope="col">Msgid</th>
                            <th scope="col">Translation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($translations as $translation)
                            @php
                                $isActiveRow = (string) old('translation_id') === (string) $translation->id;
                                $fieldErrors = $isActiveRow ? $errors->get('msgstr') : [];
                                $fieldValue = $isActiveRow ? old('msgstr', '') : ($translation->msgstr ?? '');
                            @endphp
                            <tr>
                                <td class="text-nowrap">{{ $translation->file_path }}</td>
                                <td>{{ $translation->context ?? '—' }}</td>
                                <td>{{ Str::limit($translation->msgid, 80) }}</td>
                                <td class="w-50">
                                    <form method="POST" action="{{ route('translations.update', $translation) }}" class="d-flex gap-2 align-items-start">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                                        <input type="hidden" name="translation_id" value="{{ $translation->id }}">

                                        <textarea name="msgstr" rows="1" style="text-align:right;" class="form-control form-control-sm @if ($fieldErrors) is-invalid @endif" placeholder="Enter translation">{{ $fieldValue }}</textarea>

                                        <div class="d-flex flex-row gap-1">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                Save
                                            </button>
                                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('translations.edit', $translation) }}">
                                                Details
                                            </a>
                                        </div>
                                    </form>

                                    @if ($fieldErrors)
                                        <div class="invalid-feedback d-block">
                                            {{ $fieldErrors[0] }}
                                        </div>
                                    @endif

                                    @if (session('status_id') == $translation->id && session('status'))
                                        <div class="text-success small mt-2">
                                            {{ session('status') }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    No translations found. Try adjusting your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($translations->hasPages())
                <div class="card-footer">
                    {{ $translations->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
