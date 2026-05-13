@extends('admin.layout')

@push('styles')
        <link rel="stylesheet" href="{{ asset('css/admin/import.css') }}">
@endpush

@section('content')
    <header class="top-header glass-panel import-header">
        <div class="import-header-title">
            <h1>Import Data Alumni</h1>
            <p>Gunakan file format .xlsx untuk unggah masal</p>
        </div>
        <div class="template-actions" aria-label="Aksi template import alumni">
            <button type="button" class="btn-template btn-template-outline" id="btn-show-template">
                Lihat Template Kolom
            </button>
        </div>
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
                <input type="file" id="file-input" accept=".xlsx, .xls" style="display:none;">
                <div style="display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap;">
                    <span class="custom-file-label" id="file-name-display">Pilih File Alumni</span>
                    <button
                        type="button"
                        id="btn-cancel-import"
                        style="display:none; border:none; border-radius:12px; padding:10px 14px; font-weight:800; cursor:pointer; background:#e2e8f0; color:#0f172a;"
                        title="Batalkan file yang dipilih"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <div class="table-container" id="table-wrapper" style="display: none; overflow-x: auto; max-width: 100%; border-radius: 8px;">
                <table id="preview-table" style="min-width: 1300px;"> <!-- Min-width supaya kolom tidak berdempetan -->
                    <thead>
                        <tr id="preview-head-row"></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <button id="btn-import" class="btn-tambah" style="display:none; width: 100%; margin-top: 25px; justify-content: center; padding: 15px;">
                Mulai Import Data
            </button>

            <div id="import-progress" class="glass-panel" style="display:none; margin-top: 20px; padding: 16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:10px;">
                    <div style="font-weight:600; color:#0f172a;">Memproses import...</div>
                    <div id="import-progress-text" style="font-size:12px; color:#64748b;">0/0</div>
                </div>
                <div style="width:100%; height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                    <div id="import-progress-bar" style="width:0%; height:100%; background:var(--pilkom-blue-dark); border-radius:999px;"></div>
                </div>
                <div id="import-progress-subtext" style="margin-top:10px; font-size:12px; color:#64748b;">Menyiapkan data...</div>
            </div>

            <div id="import-result" class="result-success" style="display:none;">
                <h4 style="margin-bottom: 5px;">Import Selesai!</h4>
                <p id="result-text"></p>
            </div>
        </div>
    </div>

    <div class="template-modal" id="template-modal" aria-hidden="true">
        <div class="template-modal-backdrop" data-template-close></div>
        <section class="template-modal-panel" role="dialog" aria-modal="true" aria-labelledby="template-modal-title">
            <div class="template-modal-header">
                <div>
                    <h2 id="template-modal-title">Panduan Template Kolom Excel</h2>
                    <p>Gunakan header berikut pada baris pertama file Excel.</p>
                </div>
                <div class="template-modal-actions">
                    <a href="{{ route('admin.alumni.import.template') }}" class="btn-template btn-template-primary">
                        Download Template Excel
                    </a>
                    <button type="button" class="template-modal-close" data-template-close aria-label="Tutup panduan">&times;</button>
                </div>
            </div>

            <div class="template-table-wrap">
                <table class="template-table">
                    <thead>
                        <tr>
                            <th>Header Excel</th>
                            <th>Wajib?</th>
                            <th>Keterangan</th>
                            <th>Contoh Isi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($templateColumns as $column)
                            <tr>
                                <td><code>{{ $column['header'] }}</code></td>
                                <td>
                                    @if ($column['required'])
                                        <span class="template-badge is-required">Wajib</span>
                                    @else
                                        <span class="template-badge is-optional">Opsional</span>
                                    @endif
                                </td>
                                <td>{{ $column['description'] }}</td>
                                <td>{{ $column['example'] !== '' ? $column['example'] : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="template-notes">
                <strong>Catatan:</strong>
                <ul aria-label="Catatan penting template Excel">
                    <li>Format .xlsx</li>
                    <li>Baris pertama berisi header</li>
                    <li>Header harus sama persis</li>
                    <li>Kolom wajib tidak boleh kosong</li>
                    <li>Kolom opsional boleh kosong</li>
                    <li>Ubah header hanya jika kode import disesuaikan</li>
                </ul>
            </div>
        </section>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('js/admin/import.js') }}">
    </script>
@endpush
