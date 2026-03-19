@extends('admin.layout')

@push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin/import.css') }}">
@endpush

@section('content')
    <header class="top-header glass-panel">
        <h1>Import Data Alumni</h1>
        <p style="font-size: 13px; color: #64748b;">Gunakan file format .xlsx untuk unggah masal</p>
    </header>

    <div class="import-container" >
        <div class="glass-panel" style="padding: 30px;">
            <div id="drop-area" onclick="document.getElementById('file-input').click()">
                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 15px; display: block; color: var(--pilkom-blue-dark); opacity: 0.6;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <p style="font-weight: 600; color: #1e293b;">Tarik & Lepas file Excel di sini</p>
                <p style="font-size: 12px;">Atau klik untuk memilih file dari komputer</p>
                <input type="file" id="file-input" accept=".xlsx, .xls">
                <span class="custom-file-label" id="file-name-display">Pilih File Alumni</span>
            </div>

            <div class="table-container" id="table-wrapper" style="display: none;">
                <table id="preview-table">
                    <thead>
                        <tr>
                            <th>NIM</th>
                            <th>Nama</th>
                            <th>Tahun</th>
                            <th>Perusahaan</th>
                            <th>Jabatan</th>
                            <th>Kota</th>
                            <th>Linearitas</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <button id="btn-import" class="btn-tambah" style="display:none; width: 100%; margin-top: 25px; justify-content: center; padding: 15px;">
                Mulai Import Data
            </button>

            <div id="import-result" class="result-success">
                <h4 style="margin-bottom: 5px;">Import Selesai!</h4>
                <p id="result-text"></p>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/admin/import.js') }}">
    </script>
@endpush
