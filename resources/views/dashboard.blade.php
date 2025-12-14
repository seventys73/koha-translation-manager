<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Translation Workflow</h2>
            <span class="text-muted small">Manage Koha PO lifecycle</span>
        </div>
    </x-slot>

    <div class="container py-4">
        @if ($errors->any())
            <div class="alert alert-danger">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($importSummary)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $importSummary }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h3 class="h6 mb-0">Import PO Files</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('dashboard.import.local') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" name="files[]" class="form-control form-control-sm" accept=".po,text/plain" multiple required>
                                <div class="form-text">
                                    Upload one or more Koha <code>.po</code> files into the working directory.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                Local Import
                            </button>
                        </form>
                        <form method="POST" action="{{ route('dashboard.import.remote') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                Remote Import
                            </button>
                        </form>
                        <p class="text-muted small mt-2 mb-0">
                            Source: {{ $kohaPath }}
                        </p>
                        <p class="text-muted small mb-0">
                            Target language: {{ $targetLanguage ?: 'Not set' }}
                        </p>
                        @if ($remoteImportSummary)
                            <div class="alert alert-info small mt-3 mb-0">
                                {{ $remoteImportSummary }}
                            </div>
                        @endif

                        <div class="mt-3">
                            <p class="text-muted small mb-1">Available files</p>
                            <div class="border rounded p-2 bg-light" style="max-height: 180px; overflow-y: auto;">
                                @forelse ($poFiles as $file)
                                    <div class="small">
                                        <strong>{{ $file['name'] }}</strong>
                                        <span class="text-muted">({{ $file['size'] }}, {{ $file['updated'] }})</span>
                                    </div>
                                @empty
                                    <div class="text-muted small">
                                        No files uploaded yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h3 class="h6 mb-0">Sync with Koha</h3>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <form method="POST" action="{{ route('dashboard.sync') }}">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100 mb-2">
                                Run Sync
                            </button>
                        </form>
                        <p class="text-muted small mb-4">
                            Compares your database translations with the current Koha PO files and reapplies saved strings.
                        </p>
                        @if ($syncReport)
                            <div class="alert alert-info small mb-0" role="alert">
                                {!! nl2br(e($syncReport)) !!}
                            </div>
                        @else
                            <div class="text-muted small mt-auto">
                                No sync run yet this session.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h3 class="h6 mb-0">Export &amp; Deploy</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('dashboard.export') }}">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                Export ZIP
                            </button>
                        </form>
                        <p class="text-muted small">
                            Generates fresh PO files from your saved translations and bundles them into a downloadable archive.
                        </p>
                        <form method="POST" action="{{ route('dashboard.push') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100 mt-2">
                                Push to Server
                            </button>
                        </form>
                        <p class="text-muted small mt-2 mb-0">
                            Target: {{ $kohaPath }}
                        </p>
                        @if ($pushStatus)
                            <div class="alert alert-info small mt-3 mb-0">
                                {!! nl2br(e($pushStatus)) !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
