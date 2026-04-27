<div id="modal-studi" class="profil-modal-overlay">
    <div class="profil-modal-card" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
        <button type="button" class="close-modal-btn" onclick="closeModalStudi()">&times;</button>
        <h3 style="color: #004a87; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
            Tambah Studi Lanjut
        </h3>

        <form action="<?php echo e(route('admin.studi-lanjut.store', $alumni->id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="tab-studi">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="grid-column: span 2;">
                    <label class="label-admin">Kampus</label>
                    <input type="text" name="kampus" id="studi_kampus" class="custom-input-admin" placeholder="Universitas Lambung Mangkurat" required>
                </div>

                <div style="grid-column: span 2;">
                    <label class="label-admin">Alamat Kampus (Opsional)</label>
                    <textarea name="alamat_kampus" id="studi_alamat_kampus" class="custom-input-admin" rows="2" placeholder="Alamat kampus..."></textarea>
                </div>

                <div>
                    <label class="label-admin">Kota Kampus (Opsional)</label>
                    <input type="text" name="kota_kampus" id="studi_kota_kampus" class="custom-input-admin" placeholder="Banjarmasin">
                </div>

                <div>
                    <label class="label-admin">Provinsi Kampus (Opsional)</label>
                    <input type="text" name="provinsi_kampus" id="studi_provinsi_kampus" class="custom-input-admin" placeholder="Kalimantan Selatan">
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
                    <input type="text" name="program_studi" class="custom-input-admin" placeholder="Pendidikan Komputer">
                </div>

                <div>
                    <label class="label-admin">Tahun Masuk (Opsional)</label>
                    <input type="number" name="tahun_masuk" class="custom-input-admin" min="1900" placeholder="2024">
                </div>

                <div>
                    <label class="label-admin">Tahun Lulus (Opsional)</label>
                    <input type="number" name="tahun_lulus" class="custom-input-admin" min="1900" placeholder="2026">
                </div>

                <div style="grid-column: span 2;">
                    <label class="label-admin">Status</label>
                    <select name="status" class="custom-input-admin" required>
                        <option value="Sedang Berjalan">Sedang Berjalan</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Tidak Selesai">Tidak Selesai</option>
                        <option value="Cuti">Cuti</option>
                    </select>
                </div>

                <div style="grid-column: span 2; display:flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn-tambah" onclick="cariLokasiKampusTambah()" style="width:auto; padding: 12px 18px; background:#004a87;">
                        Cari Lokasi Kampus
                    </button>
                    <small id="studi_cari_status" style="color:#94a3b8; font-weight: 600;">
                        Gunakan kampus + alamat + kota + provinsi.
                    </small>
                </div>

                <div style="grid-column: span 2;">
                    <label class="label-admin" style="display: flex; justify-content: space-between;">
                        <span>Preview Lokasi Kampus</span>
                        <small style="color: #94a3b8; font-weight: normal;">Geser pin untuk update koordinat</small>
                    </label>
                    <div id="map-studi-tambah" style="height: 200px; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 10px; z-index: 1;"></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="label-admin">Latitude (Opsional)</label>
                            <input type="text" name="latitude" id="studi_lat" class="custom-input-admin" placeholder="-3.316694">
                        </div>
                        <div>
                            <label class="label-admin">Longitude (Opsional)</label>
                            <input type="text" name="longitude" id="studi_lng" class="custom-input-admin" placeholder="114.590111">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-tambah" style="grid-column: span 2; padding: 15px; background: #0284c7; font-size: 15px;">
                    Simpan Studi Lanjut
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-studi" class="profil-modal-overlay">
    <div class="profil-modal-card" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
        <button type="button" class="close-modal-btn" onclick="closeEditModalStudi()">&times;</button>
        <h3 style="color: #004a87; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
            Edit Studi Lanjut
        </h3>

        <form id="form-edit-studi" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <input type="hidden" name="tab" value="tab-studi">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="grid-column: span 2;">
                    <label class="label-admin">Kampus</label>
                    <input type="text" name="kampus" id="edit_kampus" class="custom-input-admin" required>
                </div>

                <div style="grid-column: span 2;">
                    <label class="label-admin">Alamat Kampus (Opsional)</label>
                    <textarea name="alamat_kampus" id="edit_alamat_kampus" class="custom-input-admin" rows="2" placeholder="Alamat kampus..."></textarea>
                </div>

                <div>
                    <label class="label-admin">Kota Kampus (Opsional)</label>
                    <input type="text" name="kota_kampus" id="edit_kota_kampus" class="custom-input-admin">
                </div>

                <div>
                    <label class="label-admin">Provinsi Kampus (Opsional)</label>
                    <input type="text" name="provinsi_kampus" id="edit_provinsi_kampus" class="custom-input-admin">
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

                <div style="grid-column: span 2;">
                    <label class="label-admin">Status</label>
                    <select name="status" id="edit_status_studi" class="custom-input-admin" required>
                        <option value="Sedang Berjalan">Sedang Berjalan</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Tidak Selesai">Tidak Selesai</option>
                        <option value="Cuti">Cuti</option>
                    </select>
                </div>

                <div style="grid-column: span 2; display:flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn-tambah" onclick="cariLokasiKampusEdit()" style="width:auto; padding: 12px 18px; background:#004a87;">
                        Cari Lokasi Kampus
                    </button>
                    <small id="edit_cari_status" style="color:#94a3b8; font-weight: 600;">
                        Gunakan kampus + alamat + kota + provinsi.
                    </small>
                </div>

                <div style="grid-column: span 2;">
                    <label class="label-admin" style="display: flex; justify-content: space-between;">
                        <span>Preview Lokasi Kampus</span>
                        <small style="color: #94a3b8; font-weight: normal;">Geser pin untuk update koordinat</small>
                    </label>
                    <div id="map-studi-edit" style="height: 200px; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 10px; z-index: 1;"></div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px;">
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

                <button type="submit" class="btn-tambah" style="grid-column: span 2; padding: 15px; background: #0284c7; font-size: 15px;">
                    Update Studi Lanjut
                </button>
            </div>
        </form>
    </div>
</div>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/admin/komponen/studi-lanjut.blade.php ENDPATH**/ ?>