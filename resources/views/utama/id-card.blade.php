<div id="profil-modal-overlay" class="profil-modal-overlay ">
    <div class="profil-modal-card">
        <button class="close-modal-btn" id="close-profil-modal" title="Tutup Profil">&times;</button>

        <div class="profil-modal-header">
            <div class="profil-avatar-container">
                <img id="modal-avatar" src="https://ui-avatars.com/api/?name=Alumni&background=004a87&color=fff&size=100"
                    alt="Avatar Alumni">
            </div>
            <h2 id="modal-nama">Nama Lengkap Alumni</h2>
            @if(auth()->user()?->isAdmin())
                <div id="modal-nim" class="modal-nim">NIM: -</div>
            @endif
            <span id="modal-tahun" class="badge-tahun">Angkatan Tahun 202X</span>
        </div>

        <div class="profil-modal-body">
            <div class="info-group">
                <span class="info-icon">🏢</span>
                <div class="info-text">
                    <label id="modal-lokasi-label">Tempat Kerja</label>
                    <p id="modal-perusahaan">Nama Instansi Tempat Bekerja</p>
                </div>
            </div>

            @if(auth()->user()?->isAdmin())
            <div class="info-group">
                <span class="info-icon">📍</span>
                <div class="info-text">
                    <label id="modal-alamat-label">Alamat Kantor</label>
                    <p id="modal-alamat">-</p>
                </div>
            </div>
            @endif

            <div class="info-group">
                <span class="info-icon">💼</span>
                <div class="info-text">
                    <label>Posisi / Jabatan</label>
                    <p id="modal-jabatan">Posisi Jabatan Saat Ini</p>
                </div>
            </div>

            <div class="info-group">
                <span class="info-icon">🗂️</span>
                <div class="info-text">
                    <label>Bidang Pekerjaan</label>
                    <p id="modal-bidang-pekerjaan">-</p>
                </div>
            </div>

            @if(auth()->user()?->isAdmin())
            <div class="info-group">
                <span class="info-icon">🎓</span>
                <div class="info-text">
                    <label>Kesesuaian Bidang Ilmu</label>
                    <div id="modal-linearitas" class="status-badge status-linier">Linier</div>
                </div>
            </div>
            @endif

            @if(auth()->user()?->isAdmin())
            <div id="modal-extra-info" class="profile-extra-info" hidden>
                <div id="modal-skripsi-card" class="profile-extra-card profile-extra-card--skripsi" hidden>
                    <span class="profile-extra-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"></path>
                            <path d="M20 2H6.5A2.5 2.5 0 0 0 4 4.5v15"></path>
                            <path d="M8 6h8"></path>
                            <path d="M8 10h8"></path>
                            <path d="M8 14h5"></path>
                        </svg>
                    </span>
                    <div>
                        <span class="profile-extra-label">Judul Skripsi</span>
                        <p id="modal-judul-skripsi" class="profile-extra-title"></p>
                    </div>
                </div>

                <a id="modal-linkedin-card" class="profile-extra-card profile-extra-card--linkedin" href="#" target="_blank"
                    rel="noopener noreferrer" hidden>
                    <span class="profile-extra-icon profile-extra-icon--linkedin" aria-hidden="true">in</span>
                    <div>
                        <span class="profile-extra-label">LinkedIn</span>
                        <strong class="profile-extra-title">Lihat profil profesional</strong>
                    </div>
                </a>
            </div>
            @endif
        </div>

        <div class="profil-modal-footer">
            @if(auth()->user()?->isAdmin())
                <a
                    class="btn-edit-data"
                    id="btn-edit-data-alumni"
                    href="#"
                    data-edit-url-template="{{ route('admin.alumni.edit', ['id' => '__ALUMNI_ID__'], false) }}"
                    hidden
                >
                    <svg class="btn-edit-data-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        aria-hidden="true">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                    </svg>
                    <span>Edit Data</span>
                </a>
            @endif
            <button class="btn-tutup-bawah" id="btn-tutup-bawah">Tutup Jendela</button>
        </div>
    </div>
</div>
