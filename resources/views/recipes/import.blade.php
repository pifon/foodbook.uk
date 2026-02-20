@extends('layouts.app')
@section('title', 'Import Recipe — Foodbook')

@section('content')
<div>
    <a href="{{ route('recipes.index') }}" class="mb-6 inline-flex items-center gap-1 text-sm text-gray-600 transition hover:text-gray-900">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
        Back
    </a>

    <h1 class="mb-1 text-2xl font-bold text-gray-900">Import Recipe</h1>
    <p class="mb-6 text-gray-600">Upload a JSON file previously exported from the API.</p>

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-600">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('recipes.import.store') }}"
        enctype="multipart/form-data"
        class="space-y-6"
        id="import-form"
    >
        @csrf

        <section class="rounded-xl border border-gray-200 bg-white p-6">
            <div
                id="drop-zone"
                class="relative flex min-h-[200px] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 transition hover:border-primary-400 hover:bg-primary-50/30"
            >
                <input
                    type="file"
                    name="json_file"
                    id="json-file-input"
                    accept=".json,application/json"
                    class="absolute inset-0 cursor-pointer opacity-0"
                />
                <svg class="mb-3 h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                <p class="text-sm font-medium text-gray-700">Drop a JSON file here or click to browse</p>
                <p class="mt-1 text-xs text-gray-500">Only .json files are accepted</p>
            </div>

            <div id="file-info" class="mt-4 hidden items-center gap-3 rounded-lg bg-primary-50 px-4 py-3">
                <svg class="h-5 w-5 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span id="file-name" class="text-sm font-medium text-primary-700"></span>
            </div>

            <div id="preview-container" class="mt-4 hidden">
                <h3 class="mb-2 text-sm font-medium text-gray-700">Preview</h3>
                <pre id="json-preview" class="max-h-64 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"></pre>
            </div>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" id="import-btn" disabled
                class="rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-50">
                Import Recipe
            </button>
            <a href="{{ route('recipes.index') }}"
                class="rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
(function () {
    var dropZone   = document.getElementById('drop-zone');
    var fileInput   = document.getElementById('json-file-input');
    var fileInfo    = document.getElementById('file-info');
    var fileNameEl  = document.getElementById('file-name');
    var previewWrap = document.getElementById('preview-container');
    var previewEl   = document.getElementById('json-preview');
    var importBtn   = document.getElementById('import-btn');

    ['dragenter', 'dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.classList.add('border-primary-400', 'bg-primary-50/50');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault();
            dropZone.classList.remove('border-primary-400', 'bg-primary-50/50');
        });
    });

    dropZone.addEventListener('drop', function (e) {
        var file = e.dataTransfer.files[0];
        if (file) {
            fileInput.files = e.dataTransfer.files;
            showPreview(file);
        }
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files[0]) showPreview(fileInput.files[0]);
    });

    function showPreview(file) {
        fileNameEl.textContent = file.name + ' (' + formatBytes(file.size) + ')';
        fileInfo.classList.remove('hidden');
        fileInfo.classList.add('flex');

        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var json = JSON.parse(e.target.result);
                previewEl.textContent = JSON.stringify(json, null, 2);
                previewWrap.classList.remove('hidden');
                importBtn.disabled = false;
            } catch (err) {
                previewEl.textContent = 'Error: Invalid JSON — ' + err.message;
                previewWrap.classList.remove('hidden');
                importBtn.disabled = true;
            }
        };
        reader.readAsText(file);
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
})();
</script>
@endsection
