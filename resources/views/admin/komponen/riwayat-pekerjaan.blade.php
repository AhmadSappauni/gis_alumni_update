<div id="modal-pekerjaan" class="profil-modal-overlay">
    <div class="profil-modal-card job-modal-card">
        <button type="button" class="close-modal-btn job-modal-close" onclick="closeModalKerja()" aria-label="Tutup modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>

        <div class="job-modal-header">
            <span class="job-modal-header-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1"></path>
                    <path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"></path>
                    <path d="M3 13h18"></path>
                    <path d="M12 12v2"></path>
                </svg>
            </span>
            <div>
                <h3>Tambah Riwayat Pekerjaan</h3>
                <p>Lengkapi detail pekerjaan dan titik lokasi kantor alumni.</p>
            </div>
        </div>

        <form action="{{ route('admin.pekerjaan.store', $alumni->id) }}" method="POST">
            @csrf
            <div class="job-modal-grid">
                <div class="job-modal-span-2">
                    <label class="label-admin">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" class="custom-input-admin"
                        placeholder="PT Telkom Indonesia" required>
                </div>

                <div>
                    <label class="label-admin">Jabatan</label>
                    <input type="text" name="jabatan" class="custom-input-admin" placeholder="Software Engineer" required>
                </div>

                <div>
                    <label class="label-admin">Kota / Lokasi Kerja</label>
                    <input type="text" name="kota" id="tambah_kota" class="custom-input-admin"
                        placeholder="Jakarta Selatan" required>
                </div>

                <div>
                    <label class="label-admin">Bidang</label>
                    <input type="text" name="bidang_pekerjaan" class="custom-input-admin"
                        list="bidang-pekerjaan-options" placeholder="Pilih atau tulis bidang pekerjaan" required>
                </div>

                <div>
                    <label class="label-admin">Relevansi Pekerjaan dengan Studi</label>
                    <select name="linearitas" class="custom-input-admin" required>
                        <option value="Sangat Erat">Sangat Erat</option>
                        <option value="Erat">Erat</option>
                        <option value="Cukup Erat">Cukup Erat</option>
                        <option value="Kurang Erat">Kurang Erat</option>
                        <option value="Tidak Erat">Tidak Erat</option>
                    </select>
                </div>

                <div>
                    <label class="label-admin">Estimasi Gaji (Opsional)</label>
                    <input type="text" name="gaji_nominal" class="custom-input-admin" placeholder="Rp 5.000.000">
                </div>

                <div>
                    <label class="label-admin">Link LinkedIn (Opsional)</label>
                    <input type="url" name="link_linkedin" class="custom-input-admin"
                        placeholder="https://linkedin.com/in/username">
                </div>

                <div>
                    <label class="label-admin">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tambah_tanggal_selesai" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Masa Tunggu (bulan)</label>
                    <input type="number" name="masa_tunggu" class="custom-input-admin" min="0" placeholder="Contoh: 3">
                    <small class="job-field-help">Kosongkan jika ingin dihitung otomatis dari tahun lulus dan tanggal mulai.</small>
                </div>

                <div class="job-current-field">
                    <label class="job-checkbox-card">
                        <input type="checkbox" name="is_current_pekerjaan" id="tambah_is_current" value="1" checked>
                        <span class="job-checkbox-box" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m5 12 4 4L19 6"></path>
                            </svg>
                        </span>
                        Masih bekerja di sini
                    </label>
                </div>

                <div class="job-modal-span-2 job-map-section">
                    <label class="label-admin job-map-label">
                        <span>Peta Lokasi Kantor</span>
                        <small>Geser pin untuk mengisi alamat otomatis</small>
                    </label>
                    <div id="map-kerja-tambah" class="job-location-map"></div>
                    <textarea name="alamat_lengkap" id="tambah_alamat" class="custom-input-admin" rows="2"
                        placeholder="Pinpoint di peta..." required readonly></textarea>
                    <input type="hidden" name="latitude" id="tambah_lat" value="-3.316694">
                    <input type="hidden" name="longitude" id="tambah_lng" value="114.590111">
                </div>

                <button type="submit" class="btn-tambah job-submit-btn job-submit-btn--create">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"></path>
                        <path d="M17 21v-8H7v8"></path>
                        <path d="M7 3v5h8"></path>
                    </svg>
                    <span>Simpan Pekerjaan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-pekerjaan" class="profil-modal-overlay">
    <div class="profil-modal-card job-modal-card">
        <button type="button" class="close-modal-btn job-modal-close" onclick="closeEditModalKerja()" aria-label="Tutup modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18"></path>
                <path d="m6 6 12 12"></path>
            </svg>
        </button>

        <div class="job-modal-header">
            <span class="job-modal-header-icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                </svg>
            </span>
            <div>
                <h3>Edit Riwayat Pekerjaan</h3>
                <p>Perbarui data pekerjaan tanpa mengubah alur pemetaan yang sudah ada.</p>
            </div>
        </div>

        <form id="form-edit-pekerjaan" method="POST">
            @csrf
            @method('PUT')
            <div class="job-modal-grid">
                <div class="job-modal-span-2">
                    <label class="label-admin">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" id="edit_perusahaan" class="custom-input-admin" required>
                </div>

                <div>
                    <label class="label-admin">Jabatan</label>
                    <input type="text" name="jabatan" id="edit_jabatan" class="custom-input-admin" required>
                </div>

                <div>
                    <label class="label-admin">Kota / Lokasi Kerja</label>
                    <input type="text" name="kota" id="edit_kota" class="custom-input-admin" required>
                </div>

                <div>
                    <label class="label-admin">Bidang</label>
                    <input type="text" name="bidang_pekerjaan" id="edit_bidang" class="custom-input-admin"
                        list="bidang-pekerjaan-options" placeholder="Pilih atau tulis bidang pekerjaan" required>
                </div>

                <div>
                    <label class="label-admin">Linearitas</label>
                    <select name="linearitas" id="edit_linearitas" class="custom-input-admin" required>
                        <option value="Sangat Erat">Sangat Erat</option>
                        <option value="Erat">Erat</option>
                        <option value="Cukup Erat">Cukup Erat</option>
                        <option value="Kurang Erat">Kurang Erat</option>
                        <option value="Tidak Erat">Tidak Erat</option>
                    </select>
                </div>

                <div>
                    <label class="label-admin">Estimasi Gaji (Opsional)</label>
                    <input type="text" name="gaji_nominal" id="edit_gaji" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Link LinkedIn (Opsional)</label>
                    <input type="url" name="link_linkedin" id="edit_linkedin" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Masa Tunggu (bulan)</label>
                    <input type="number" name="masa_tunggu" id="edit_masa_tunggu" class="custom-input-admin" min="0" placeholder="Contoh: 3">
                    <small class="job-field-help">Kosongkan jika ingin dihitung otomatis dari tahun lulus dan tanggal mulai.</small>
                </div>

                <div class="job-current-field">
                    <label class="job-checkbox-card">
                        <input type="checkbox" name="is_current_pekerjaan" id="edit_is_current" value="1">
                        <span class="job-checkbox-box" aria-hidden="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m5 12 4 4L19 6"></path>
                            </svg>
                        </span>
                        Masih bekerja di sini
                    </label>
                </div>

                <div class="job-modal-span-2 job-map-section">
                    <label class="label-admin job-map-label">
                        <span>Peta Lokasi Kantor</span>
                        <small>Geser pin untuk update alamat</small>
                    </label>
                    <div id="map-kerja-edit" class="job-location-map"></div>
                    <textarea name="alamat_lengkap" id="edit_alamat" class="custom-input-admin" rows="2" required readonly></textarea>
                    <input type="hidden" name="latitude" id="edit_lat">
                    <input type="hidden" name="longitude" id="edit_lng">
                </div>

                <button type="submit" class="btn-tambah job-submit-btn job-submit-btn--edit">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                    <span>Update Pekerjaan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<datalist id="bidang-pekerjaan-options">
    <option value="IT & Software">
    <option value="Pendidikan / Guru">
    <option value="Pemerintahan">
    <option value="Wiraswasta">
</datalist>
