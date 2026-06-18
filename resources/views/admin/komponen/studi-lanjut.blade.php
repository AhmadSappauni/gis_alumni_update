<div id="modal-studi" class="profil-modal-overlay">
    <div class="profil-modal-card job-modal-card study-modal-card">
        <button type="button" class="close-modal-btn job-modal-close" onclick="closeModalStudi()" aria-label="Tutup modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>

        <div class="job-modal-header study-modal-header">
            <span class="job-modal-header-icon study-modal-header-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 10 9-5 9 5-9 5Z"></path>
                    <path d="M7 12v5c3 2 7 2 10 0v-5"></path>
                    <path d="M21 10v6"></path>
                </svg>
            </span>
            <div>
                <h3>Tambah Studi Lanjut</h3>
                <p>Lengkapi informasi pendidikan dan titik lokasi kampus alumni.</p>
            </div>
        </div>

        <form action="{{ route('admin.studi-lanjut.store', $alumni->id) }}" method="POST">
            @csrf
            <input type="hidden" name="tab" value="tab-studi">

            <div class="job-modal-grid study-modal-grid">
                <div class="job-modal-span-2">
                    <label class="label-admin">Nama Kampus</label>
                    <input type="text" name="kampus" id="studi_kampus" class="custom-input-admin"
                        placeholder="Universitas Lambung Mangkurat" required>
                </div>

                <div>
                    <label class="label-admin">Jenjang</label>
                    <select name="jenjang" class="custom-input-admin" required>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                        <option value="Profesi">Pendidikan Profesi Guru</option>
                        <option value="Sertifikasi">Sertifikasi</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="label-admin">Program Studi (Opsional)</label>
                    <input type="text" name="program_studi" class="custom-input-admin"
                        placeholder="Pendidikan Komputer">
                </div>

                <div>
                    <label class="label-admin">Tahun Masuk (Opsional)</label>
                    <input type="number" name="tahun_masuk" class="custom-input-admin" min="1900"
                        placeholder="2024">
                </div>

                <div>
                    <label class="label-admin">Tahun Lulus (Opsional)</label>
                    <input type="number" name="tahun_lulus" class="custom-input-admin" min="1900"
                        placeholder="2026">
                </div>

                <div class="job-modal-span-2">
                    <label class="label-admin">Status Studi</label>
                    <select name="status" class="custom-input-admin" required>
                        <option value="Sedang Berjalan">Sedang Berjalan</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Tidak Selesai">Tidak Selesai</option>
                        <option value="Cuti">Cuti</option>
                    </select>
                </div>

                <div class="job-modal-span-2 study-section-heading">
                    <span class="study-section-icon" aria-hidden="true">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="2.5"></circle>
                        </svg>
                    </span>
                    <div>
                        <strong>Lokasi Kampus</strong>
                        <small>Isi data lokasi, lalu cari untuk menempatkan pin secara otomatis.</small>
                    </div>
                </div>

                <div class="job-modal-span-2">
                    <label class="label-admin">Alamat Kampus (Opsional)</label>
                    <textarea name="alamat_kampus" id="studi_alamat_kampus" class="custom-input-admin study-address-input"
                        rows="2" placeholder="Jalan, kecamatan, atau detail alamat kampus"></textarea>
                </div>

                <div>
                    <label class="label-admin">Kota Kampus (Opsional)</label>
                    <input type="text" name="kota_kampus" id="studi_kota_kampus" class="custom-input-admin"
                        placeholder="Banjarmasin">
                </div>

                <div>
                    <label class="label-admin">Provinsi Kampus (Opsional)</label>
                    <input type="text" name="provinsi_kampus" id="studi_provinsi_kampus" class="custom-input-admin"
                        placeholder="Kalimantan Selatan">
                </div>

                <div class="job-modal-span-2 study-search-row">
                    <button type="button" class="study-search-btn" onclick="cariLokasiKampusTambah()">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>
                        <span>Cari Lokasi Kampus</span>
                    </button>
                    <small id="studi_cari_status" class="study-search-status" data-state="idle"
                        role="status" aria-live="polite">
                        Gunakan nama kampus, alamat, kota, atau provinsi.
                    </small>
                </div>

                <div class="job-modal-span-2 job-map-section study-map-section">
                    <label class="label-admin job-map-label">
                        <span>Preview Lokasi Kampus</span>
                        <small>Geser pin untuk memperbarui koordinat</small>
                    </label>
                    <div id="map-studi-tambah" class="job-location-map study-location-map"></div>

                    <div class="study-coordinate-grid">
                        <div>
                            <label class="label-admin">Latitude (Opsional)</label>
                            <input type="text" name="latitude" id="studi_lat" class="custom-input-admin"
                                placeholder="-3.316694">
                        </div>
                        <div>
                            <label class="label-admin">Longitude (Opsional)</label>
                            <input type="text" name="longitude" id="studi_lng" class="custom-input-admin"
                                placeholder="114.590111">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-tambah job-submit-btn study-submit-btn study-submit-btn--create">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path>
                        <path d="M17 21v-8H7v8"></path>
                        <path d="M7 3v5h8"></path>
                    </svg>
                    <span>Simpan Studi Lanjut</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-studi" class="profil-modal-overlay">
    <div class="profil-modal-card job-modal-card study-modal-card">
        <button type="button" class="close-modal-btn job-modal-close" onclick="closeEditModalStudi()" aria-label="Tutup modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>

        <div class="job-modal-header study-modal-header">
            <span class="job-modal-header-icon study-modal-header-icon study-modal-header-icon--edit" aria-hidden="true">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                </svg>
            </span>
            <div>
                <h3>Edit Studi Lanjut</h3>
                <p>Perbarui informasi pendidikan dan lokasi kampus alumni.</p>
            </div>
        </div>

        <form id="form-edit-studi" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="tab" value="tab-studi">

            <div class="job-modal-grid study-modal-grid">
                <div class="job-modal-span-2">
                    <label class="label-admin">Nama Kampus</label>
                    <input type="text" name="kampus" id="edit_kampus" class="custom-input-admin" required>
                </div>

                <div>
                    <label class="label-admin">Jenjang</label>
                    <select name="jenjang" id="edit_jenjang" class="custom-input-admin" required>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                        <option value="Profesi">Pendidikan Profesi Guru</option>
                        <option value="Sertifikasi">Sertifikasi</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="label-admin">Program Studi (Opsional)</label>
                    <input type="text" name="program_studi" id="edit_program_studi" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Tahun Masuk (Opsional)</label>
                    <input type="number" name="tahun_masuk" id="edit_tahun_masuk" class="custom-input-admin" min="1900">
                </div>

                <div>
                    <label class="label-admin">Tahun Lulus (Opsional)</label>
                    <input type="number" name="tahun_lulus" id="edit_tahun_lulus" class="custom-input-admin" min="1900">
                </div>

                <div class="job-modal-span-2">
                    <label class="label-admin">Status Studi</label>
                    <select name="status" id="edit_status_studi" class="custom-input-admin" required>
                        <option value="Sedang Berjalan">Sedang Berjalan</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Tidak Selesai">Tidak Selesai</option>
                        <option value="Cuti">Cuti</option>
                    </select>
                </div>

                <div class="job-modal-span-2 study-section-heading">
                    <span class="study-section-icon" aria-hidden="true">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="2.5"></circle>
                        </svg>
                    </span>
                    <div>
                        <strong>Lokasi Kampus</strong>
                        <small>Perbarui data lokasi atau geser pin ke posisi yang lebih tepat.</small>
                    </div>
                </div>

                <div class="job-modal-span-2">
                    <label class="label-admin">Alamat Kampus (Opsional)</label>
                    <textarea name="alamat_kampus" id="edit_alamat_kampus" class="custom-input-admin study-address-input"
                        rows="2" placeholder="Jalan, kecamatan, atau detail alamat kampus"></textarea>
                </div>

                <div>
                    <label class="label-admin">Kota Kampus (Opsional)</label>
                    <input type="text" name="kota_kampus" id="edit_kota_kampus" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Provinsi Kampus (Opsional)</label>
                    <input type="text" name="provinsi_kampus" id="edit_provinsi_kampus" class="custom-input-admin">
                </div>

                <div class="job-modal-span-2 study-search-row">
                    <button type="button" class="study-search-btn study-search-btn--edit" onclick="cariLokasiKampusEdit()">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m20 20-4-4"></path>
                        </svg>
                        <span>Cari Lokasi Kampus</span>
                    </button>
                    <small id="edit_cari_status" class="study-search-status" data-state="idle"
                        role="status" aria-live="polite">
                        Gunakan nama kampus, alamat, kota, atau provinsi.
                    </small>
                </div>

                <div class="job-modal-span-2 job-map-section study-map-section">
                    <label class="label-admin job-map-label">
                        <span>Preview Lokasi Kampus</span>
                        <small>Geser pin untuk memperbarui koordinat</small>
                    </label>
                    <div id="map-studi-edit" class="job-location-map study-location-map"></div>

                    <div class="study-coordinate-grid">
                        <div>
                            <label class="label-admin">Latitude (Opsional)</label>
                            <input type="text" name="latitude" id="edit_studi_lat" class="custom-input-admin">
                        </div>
                        <div>
                            <label class="label-admin">Longitude (Opsional)</label>
                            <input type="text" name="longitude" id="edit_studi_lng" class="custom-input-admin">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-tambah job-submit-btn study-submit-btn study-submit-btn--edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <span>Update Studi Lanjut</span>
                </button>
            </div>
        </form>
    </div>
</div>
