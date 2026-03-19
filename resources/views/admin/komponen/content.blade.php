<main class="main-content">
    <div id="card-view-wrapper">
        <div id="card-view" class="cards-grid">
            @foreach ($dataAlumni as $alumni)
                <div class="data-card glass-panel">
                    <div class="card-profile-img">
                        @if ($alumni->foto_profil)
                            <img src="{{ asset('storage/' . $alumni->foto_profil) }}"
                                alt="Foto {{ $alumni->nama_lengkap }}">
                        @else
                            <div class="img-placeholder">
                                {{ substr($alumni->nama_lengkap, 0, 1) }}
                            </div>
                        @endif
                    </div>

                    <div class="card-header">
                        <div>
                            <h3>{{ $alumni->nama_lengkap }}</h3>
                            <div
                                style="font-size:11px; color:var(--pilkom-blue-dark); font-weight:700; margin-top:3px;">
                                {{ $alumni->nim }}
                            </div>
                        </div>
                        <span>Lulusan '{{ substr($alumni->tahun_lulus, 2) }}</span>
                    </div>

                    <div class="card-body">
                        @if ($alumni->pekerjaan)
                            <div class="info-row">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                                <b>{{ $alumni->pekerjaan->nama_perusahaan }}</b>
                            </div>
                            <div class="info-row">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span>{{ $alumni->pekerjaan->jabatan }}</span>
                            </div>
                        @else
                            <div
                                style="padding: 10px; text-align:center; background:rgba(255,255,255,0.4); border-radius:10px; border:1px dashed #cbd5e1;">
                                <span style="color:#64748b; font-style:italic; font-size:12px;">Belum mengisi data
                                    pekerjaan</span>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer">
                        @if ($alumni->pekerjaan && $alumni->pekerjaan->linearitas == 'Linier')
                            <span class="badge-linier">Linier</span>
                        @elseif($alumni->pekerjaan && $alumni->pekerjaan->linearitas == 'Tidak Linier')
                            <span class="badge-tidak">Tidak Linier</span>
                        @else
                            <span></span>
                        @endif

                        <div class="action-buttons">
                            <a href="#" class="btn-icon edit" title="Edit Data">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                            </a>
                            <a href="#" class="btn-icon delete" title="Hapus Data">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="pagination-wrapper"
            style="padding: 15px 25px; border-top: 1px solid rgba(0,0,0,0.05); background: rgba(255,255,255,0.5);">
            {{ $dataAlumni->links() }}
        </div>
        <div id="card-empty" class="no-results" style="display: none;">
            <span class="no-results-icon">🔍</span>
            <p>Ups! Alumni yang kamu cari tidak ditemukan.</p>
        </div>
    </div>

    <div id="list-view" class="glass-panel" style="display: none; padding: 0; overflow: visible; margin-top: 10px;">

        <div class="table-scroll" style="max-height: 480px; overflow-y: auto;">
            <table class="alumni-table" style="width: 100%; border-collapse: collapse;">
                <thead
                    style="position: sticky; top: 0; z-index: 10; background: #f8fafc; box-shadow: 0 1px 0 rgba(0,0,0,0.05);">
                    <tr>
                        <th>Alumni</th>
                        <th>NIM</th>
                        <th>Perusahaan</th>
                        <th>Jabatan</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="main-alumni-data">
                    @foreach ($dataAlumni as $alumni)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    @if ($alumni->foto_profil)
                                        <img src="{{ asset('storage/' . $alumni->foto_profil) }}"
                                            style="width:30px; height:30px; border-radius:8px; object-fit:cover;">
                                    @else
                                        <div class="avatar-small">{{ substr($alumni->nama_lengkap, 0, 1) }}</div>
                                    @endif
                                    <span
                                        style="font-weight: 700; color: var(--pilkom-blue-dark);">{{ $alumni->nama_lengkap }}</span>
                                </div>
                            </td>
                            <td><code style="font-size: 12px; color: #64748b;">{{ $alumni->nim }}</code></td>
                            <td>{{ $alumni->pekerjaan->nama_perusahaan ?? '-' }}</td>
                            <td>{{ $alumni->pekerjaan->jabatan ?? '-' }}</td>
                            <td>
                                <div style="display: flex; justify-content: center; gap: 8px;">
                                    <a href="#" class="action-btn-small edit"><svg width="14" height="14"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                            </path>
                                        </svg></a>
                                    <button class="action-btn-small delete"><svg width="14" height="14"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tbody id="list-empty" style="display: none;">
                    <tr class="list-empty-row">
                        <td colspan="5">
                            <div class="list-empty-content">
                                <span class="no-results-icon" style="font-size: 40px;">📂</span>
                                <p>Data tidak ditemukan dalam daftar ini.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper"
            style="padding: 15px 25px; border-top: 1px solid rgba(0,0,0,0.05); background: rgba(255,255,255,0.5);">
            {{ $dataAlumni->links() }}
        </div>
    </div>
</main>
