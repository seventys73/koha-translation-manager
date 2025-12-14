<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">General Settings</h1>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('dashboard') }}">Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="container py-4">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h2 class="h6 mb-0">Koha Translation Directory</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('settings.update') }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label for="koha_path" class="form-label">Directory path</label>
                                <input
                                    type="text"
                                    id="koha_path"
                                    name="koha_path"
                                    value="{{ old('koha_path', $kohaPath) }}"
                                    class="form-control @error('koha_path') is-invalid @enderror"
                                    placeholder="/usr/share/koha/misc/translator/po"
                                    required
                                >
                                @error('koha_path')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">
                                        Path used for remote import and push-to-server operations.
                                    </div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="target_language" class="form-label">Target Language</label>
                                <input
                                    type="text"
                                    id="target_language"
                                    name="target_language"
                                    value="{{ old('target_language', $targetLanguage) }}"
                                    class="form-control @error('target_language') is-invalid @enderror"
                                >
                                <div class="form-text">
                                    Input language code like ar-Arab.
                                </div>
                                @error('target_language')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
