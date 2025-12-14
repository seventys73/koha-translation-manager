<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-0">Edit Translation</h1>
                <small class="text-muted">{{ $translation->file_path }}</small>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('translations.index') }}">&larr; Back</a>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <dl class="row small mb-4">
                            <dt class="col-sm-3 text-muted">Context</dt>
                            <dd class="col-sm-9">{{ $translation->context ?? '—' }}</dd>

                            <dt class="col-sm-3 text-muted">Msgid</dt>
                            <dd class="col-sm-9">
                                <pre class="mb-0 bg-light p-3 rounded">{{ $translation->msgid }}</pre>
                            </dd>
                        </dl>

                        <form method="POST" action="{{ route('translations.update', $translation) }}">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="msgstr" class="form-label">Translated text</label>
                                <textarea id="msgstr" name="msgstr" rows="6" class="form-control @error('msgstr') is-invalid @enderror">{{ old('msgstr', $translation->msgstr) }}</textarea>
                                @error('msgstr')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-outline-secondary" href="{{ route('translations.index') }}">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Translation</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body small text-muted">
                        <p class="mb-2"><strong>ID:</strong> {{ $translation->id }}</p>
                        <p class="mb-2"><strong>Checksum:</strong> {{ $translation->checksum ?? 'Not set' }}</p>
                        <p class="mb-2"><strong>Created:</strong> {{ $translation->created_at?->toDayDateTimeString() ?? '—' }}</p>
                        <p class="mb-0"><strong>Updated:</strong> {{ $translation->updated_at?->toDayDateTimeString() ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
