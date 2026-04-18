<div id="modal-pekerjaan" class="profil-modal-overlay">
    <div class="profil-modal-card" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
        <button type="button" class="close-modal-btn" onclick="closeModalKerja()">&times;</button>
        <h3
            style="color: #004a87; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
            Tambah Riwayat Pekerjaan</h3>

        <form action="<?php echo e(route('admin.pekerjaan.store', $alumni->id)); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="grid-column: span 2;">
                    <label class="label-admin">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" class="custom-input-admin"
                        placeholder="PT Telkom Indonesia" required>
                </div>
                <div>
                    <label class="label-admin">Jabatan</label>
                    <input type="text" name="jabatan" class="custom-input-admin" placeholder="Software Engineer"
                        required>
                </div>
                <div>
                    <label class="label-admin">Kota / Lokasi Kerja</label>
                    <input type="text" name="kota" id="tambah_kota"
                        class="custom-input-admin"
                        placeholder="Jakarta Selatan" required>
                </div>
                <div>
                    <label class="label-admin">Bidang</label>
                    <select name="bidang_pekerjaan" class="custom-input-admin" required>
                        <option value="IT & Software">IT & Software</option>
                        <option value="Pendidikan / Guru">Pendidikan / Guru</option>
                        <option value="Pemerintahan">Pemerintahan</option>
                        <option value="Wiraswasta">Wiraswasta</option>
                    </select>
                </div>
                <div>
                    <label class="label-admin">Linearitas</label>
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

                <div style="grid-column: span 2; margin-top: 10px;">
                    <label class="label-admin" style="display: flex; justify-content: space-between;">
                        <span>Peta Lokasi Kantor</span>
                        <small style="color: #94a3b8; font-weight: normal;">Geser pin untuk mengisi alamat
                            otomatis</small>
                    </label>
                    <div id="map-kerja-tambah"
                        style="height: 200px; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 10px; z-index: 1;">
                    </div>
                    <textarea name="alamat_lengkap" id="tambah_alamat" class="custom-input-admin" rows="2"
                        placeholder="Pinpoint di peta..." required readonly></textarea>
                    <input type="hidden" name="latitude" id="tambah_lat" value="-3.316694">
                    <input type="hidden" name="longitude" id="tambah_lng" value="114.590111">
                </div>

                <button type="submit" class="btn-tambah"
                    style="grid-column: span 2; padding: 15px; background: #10b981; font-size: 15px;">💾 Simpan
                    Pekerjaan</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-pekerjaan" class="profil-modal-overlay">
    <div class="profil-modal-card" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
        <button type="button" class="close-modal-btn" onclick="closeEditModalKerja()">&times;</button>
        <h3
            style="color: #004a87; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
            Edit Riwayat Pekerjaan</h3>

        <form id="form-edit-pekerjaan" method="POST">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="grid-column: span 2;">
                    <label class="label-admin">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" id="edit_perusahaan" class="custom-input-admin"
                        required>
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
                    <select name="bidang_pekerjaan" id="edit_bidang" class="custom-input-admin" required>
                        <option value="IT & Software">IT & Software</option>
                        <option value="Pendidikan / Guru">Pendidikan / Guru</option>
                        <option value="Pemerintahan">Pemerintahan</option>
                        <option value="Wiraswasta">Wiraswasta</option>
                    </select>
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

                <div style="grid-column: span 2; margin-top: 10px;">
                    <label class="label-admin" style="display: flex; justify-content: space-between;">
                        <span>Peta Lokasi Kantor</span>
                        <small style="color: #94a3b8; font-weight: normal;">Geser pin untuk update alamat</small>
                    </label>
                    <div id="map-kerja-edit"
                        style="height: 200px; border-radius: 12px; border: 2px solid #e2e8f0; margin-bottom: 10px; z-index: 1;">
                    </div>
                    <textarea name="alamat_lengkap" id="edit_alamat" class="custom-input-admin" rows="2" required readonly></textarea>
                    <input type="hidden" name="latitude" id="edit_lat">
                    <input type="hidden" name="longitude" id="edit_lng">
                </div>

                <button type="submit" class="btn-tambah"
                    style="grid-column: span 2; padding: 15px; background: #0284c7; font-size: 15px;">💾 Update
                    Pekerjaan</button>
            </div>
        </form>
    </div>
</div>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/admin/komponen/riwayat-pekerjaan.blade.php ENDPATH**/ ?>