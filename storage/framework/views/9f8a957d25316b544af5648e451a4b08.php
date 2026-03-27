<main class="main-content">
    <div id="card-view-wrapper">
        <div id="card-view" class="cards-grid">
            <?php $__currentLoopData = $dataAlumni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $pekerjaanAktif = $alumni->pekerjaans->where('is_current', true)->first();
            ?>
                <div class="data-card glass-panel">
                    <div class="card-profile-img">
                        <?php if($alumni->foto_profil): ?>
                            <img src="<?php echo e(asset('storage/' . $alumni->foto_profil)); ?>"
                                alt="Foto <?php echo e($alumni->nama_lengkap); ?>">
                        <?php else: ?>
                            <div class="img-placeholder">
                                <?php echo e(substr($alumni->nama_lengkap, 0, 1)); ?>

                            </div>
                        <?php endif; ?>
                    </div>

                    <?php
                        $jumlahPekerjaanAktif = $alumni->pekerjaans->whereIn('status_karir', ['Utama', 'Sampingan'])->count();
                    ?>

                    <?php if($jumlahPekerjaanAktif > 1): ?>
                        <div title="Memiliki <?php echo e($jumlahPekerjaanAktif); ?> Pekerjaan Aktif" 
                            style="position: absolute; top: 15px; right: 15px; background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 8px; font-size: 10px; font-weight: 800; border: 1px solid #f59e0b; display: flex; align-items: center; gap: 4px; z-index: 10;">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            MULTI-JOB
                        </div>
                    <?php endif; ?>

                    <div class="card-header">
                        <div>
                            <h3><?php echo e($alumni->nama_lengkap); ?></h3>
                            <div
                                style="font-size:11px; color:var(--pilkom-blue-dark); font-weight:700; margin-top:3px;">
                                <?php echo e($alumni->nim); ?>

                            </div>
                        </div>
                        <span>Lulusan '<?php echo e(substr($alumni->tahun_lulus, 2)); ?></span>
                    </div>

                    <div class="card-body">
                        <div style="margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                            <div class="info-row" style="font-size: 11px; margin-bottom: 4px;">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px;"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span><?php echo e($alumni->email ?? '-'); ?></span>
                            </div>
                            <div class="info-row" style="font-size: 11px;">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:14px;"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <span><?php echo e($alumni->no_hp ?? '-'); ?></span>
                            </div>
                        </div>

                        <?php if($pekerjaanAktif): ?>
                            <div class="info-row">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <b><?php echo e($pekerjaanAktif->nama_perusahaan); ?></b>
                            </div>
                            <div class="info-row">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span><?php echo e($pekerjaanAktif->jabatan); ?></span>
                            </div>
                        <?php else: ?>
                            <div style="padding: 10px; text-align:center; background:rgba(255,255,255,0.4); border-radius:10px; border:1px dashed #cbd5e1;">
                                <span style="color:#64748b; font-style:italic; font-size:12px;">Belum bekerja/Mencari kerja</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <?php if($alumni->pekerjaan && $alumni->pekerjaan->linearitas == 'Linier'): ?>
                            <span class="badge-linier">Linier</span>
                        <?php elseif($alumni->pekerjaan && $alumni->pekerjaan->linearitas == 'Tidak Linier'): ?>
                            <span class="badge-tidak">Tidak Linier</span>
                        <?php else: ?>
                            <span></span>
                        <?php endif; ?>

                        <div class="action-buttons">
                            <button class="btn-icon view" onclick="document.getElementById('modal-profil-<?php echo e($alumni->nim); ?>').style.display='flex'" title="Lihat Profil Lengkap">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <a href="<?php echo e(route('admin.alumni.edit', $alumni->nim)); ?>" class="btn-icon edit" title="Edit Data">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                    </path>
                                </svg>
                            </a>
                            <button type="button" class="btn-icon delete" 
                                    onclick="confirmDelete('<?php echo e($alumni->nim); ?>', '<?php echo e($alumni->nama_lengkap); ?>')" 
                                    title="Hapus Data">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="pagination-footer d-flex justify-content-between align-items-center" style="padding: 15px 25px; border-top: 1px solid rgba(0,0,0,0.05); background: rgba(255,255,255,0.3);">
            <div class="pagination-links">
                <?php echo e($dataAlumni->links('pagination::bootstrap-5')); ?>

            </div>
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
                        <th>Kontak</th>
                        <th>Perusahaan</th>
                        <th>Jabatan</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="main-alumni-data">
                    <?php $__currentLoopData = $dataAlumni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $pekerjaanAktif = $alumni->pekerjaans->where('is_current', true)->first();
                    ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if($alumni->foto_profil): ?>
                                        <img src="<?php echo e(asset('storage/' . $alumni->foto_profil)); ?>"
                                            style="width:30px; height:30px; border-radius:8px; object-fit:cover;">
                                    <?php else: ?>
                                        <div class="avatar-small"><?php echo e(substr($alumni->nama_lengkap, 0, 1)); ?></div>
                                    <?php endif; ?>
                                    <span
                                        style="font-weight: 700; color: var(--pilkom-blue-dark);"><?php echo e($alumni->nama_lengkap); ?></span>
                                </div>
                            </td>
                            <td><code style="font-size: 12px; color: #64748b;"><?php echo e($alumni->nim); ?></code></td>
                            <td>
                                <div style="font-size: 12px; line-height: 1.4; color: #475569;">
                                    <div> <?php echo e($alumni->email ?? '-'); ?></div>
                                    <div> <?php echo e($alumni->no_hp ?? '-'); ?></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 12px; line-height: 1.4; color: #475569;">
                                    <div> <?php echo e($alumni->pekerjaans->where('is_current', true)->first()->nama_perusahaan ?? '-'); ?></div>
                                    <div> <?php echo e($alumni->pekerjaans->where('is_current', true)->first()->kota ?? '-'); ?></div>
                                </div>
                            </td>
                            <td><?php echo e($alumni->pekerjaans->where('is_current', true)->first()->jabatan ?? '-'); ?></td>
                            <td>
                                <div style="display: flex; justify-content: center; gap: 8px;">
                                    <button class="action-btn-small view" onclick="document.getElementById('modal-profil-<?php echo e($alumni->nim); ?>').style.display='flex'" title="Lihat Profil Lengkap">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <a href="<?php echo e(route('admin.alumni.edit', $alumni->nim)); ?>" class="action-btn-small edit"><svg width="14" height="14"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                            </path>
                                        </svg></a>
                                    <button type="button" class="action-btn-small delete" 
                                            onclick="confirmDelete('<?php echo e($alumni->nim); ?>', '<?php echo e($alumni->nama_lengkap); ?>')">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
        <div class="pagination-footer d-flex justify-content-between align-items-center" style="padding: 15px 25px; border-top: 1px solid rgba(0,0,0,0.05); background: rgba(255,255,255,0.3);">
            <div class="pagination-links">
                <?php echo e($dataAlumni->links('pagination::bootstrap-5')); ?>

            </div>
        </div>
    </div>
    <?php $__currentLoopData = $dataAlumni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alumni): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php echo $__env->make('admin.komponen.modal-profil', ['alumni' => $alumni], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</main>
<?php /**PATH D:\Aplikasi_Skripsi\gis-alumni\resources\views/admin/komponen/content.blade.php ENDPATH**/ ?>