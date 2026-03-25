<div id="profil-modal-overlay" class="profil-modal-overlay">
    <div class="profil-modal-card">
        <button class="close-modal-btn" id="close-profil-modal" title="Tutup">&times;</button>

        <div class="profil-modal-header text-center">
            <div class="profil-avatar-outer">
                <img id="modal-avatar" src="" alt="Avatar Alumni">
            </div>
            <h2 id="modal-nama" style="color: var(--pilkom-blue-dark); font-weight: 800; margin-top: 15px;">Nama Alumni</h2>
            <div style="display: flex; justify-content: center; gap: 10px; margin-top: 8px;">
                <span id="modal-nim" class="badge-nim">NIM: -</span>
                <span id="modal-tahun" class="badge-tahun">Lulusan -</span>
            </div>
        </div>

        <div class="profil-modal-body">
    <div class="info-group-full">
        <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <div class="info-text">
            <label>Judul Skripsi</label>
            <p id="modal-skripsi" style="font-style: italic; font-size: 13px; font-weight: 500;">-</p>
        </div>
    </div>

    <div class="row-grid">
        <div class="info-group">
            <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            <div class="info-text"><label>Instansi</label><p id="modal-perusahaan">-</p></div>
        </div>
        <div class="info-group">
            <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            <div class="info-text"><label>Bidang</label><p id="modal-bidang">-</p></div>
        </div>
        <div class="info-group">
            <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <div class="info-text"><label>Jabatan</label><p id="modal-jabatan">-</p></div>
        </div>
        <div class="info-group">
            <svg class="info-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <div class="info-text"><label>Gaji</label><p id="modal-gaji">-</p></div>
        </div>
        <div class="info-group" id="linkedin-container"> <div class="info-icon">🔗</div>
            <div class="info-text">
                <label>LinkedIn</label>
                <a id="modal-linkedin" href="#" target="_blank" style="color: #0077b5; font-weight: 700; text-decoration: none;">Buka Profil Profesional ↗</a>
            </div>
        </div>
    </div>
</div>

        <div class="profil-modal-footer">
            <div id="modal-linearitas" class="status-badge">Linearitas</div>
            <button class="btn-tutup-modal" id="btn-tutup-bawah">Tutup Jendela</button>
        </div>
    </div>
</div><?php /**PATH D:\gis-alumni\resources\views/admin/komponen/modal-profil.blade.php ENDPATH**/ ?>