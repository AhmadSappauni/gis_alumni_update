<div id="modal-profil-<?php echo e($alumni->nim); ?>" class="profil-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    
    <div class="profil-modal-card" style="background: white; width: 100%; max-width: 700px; border-radius: 20px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative;">
        
        <button onclick="document.getElementById('modal-profil-<?php echo e($alumni->nim); ?>').style.display='none'" style="position: absolute; right: 20px; top: 20px; background: rgba(255,255,255,0.2); border: none; font-size: 20px; color: white; cursor: pointer; z-index: 10;">&times;</button>

        <div style="background: linear-gradient(135deg, #004a87, #006bbf); padding: 30px; display: flex; gap: 20px; align-items: center;">
            <img src="<?php echo e($alumni->foto_profil ? asset('storage/' . $alumni->foto_profil) : '/default.png'); ?>" 
                 style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid white; object-fit: cover;">
            <div style="color: white;">
                <h2 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 800;"><?php echo e($alumni->nama_lengkap); ?></h2>
                <div style="display: flex; gap: 10px; font-size: 13px; opacity: 0.9;">
                    <span style="background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">NIM: <?php echo e($alumni->nim); ?></span>
                    <span style="background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">Lulusan: <?php echo e($alumni->tahun_lulus); ?></span>
                </div>
            </div>
        </div>

        <div style="padding: 25px; overflow-y: auto; background: #f8fafc;">
            
            <div style="background: white; padding: 20px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 25px;">
                <h4 style="color: #004a87; margin: 0 0 15px 0; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Informasi Pribadi</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <small style="color: #64748b; font-weight: bold; display: block;">Email</small>
                        <span style="color: #1e293b; font-size: 14px;"><?php echo e($alumni->email ?? '-'); ?></span>
                    </div>
                    <div>
                        <small style="color: #64748b; font-weight: bold; display: block;">No. WhatsApp</small>
                        <span style="color: #1e293b; font-size: 14px;"><?php echo e($alumni->no_hp ?? '-'); ?></span>
                    </div>
                    <div style="grid-column: span 2;">
                        <small style="color: #64748b; font-weight: bold; display: block;">Judul Skripsi</small>
                        <p style="margin: 5px 0 0; color: #1e293b; font-size: 14px; font-style: italic; line-height: 1.5;">"<?php echo e($alumni->judul_skripsi ?? 'Belum ada data'); ?>"</p>
                    </div>
                </div>
            </div>

            <h4 style="color: #004a87; margin: 0 0 15px 0; font-size: 16px; display: flex; align-items: center; justify-content: space-between;">
                Riwayat Pekerjaan
                <span style="font-size: 12px; background: #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 6px;"><?php echo e($alumni->pekerjaans->count()); ?> Data</span>
            </h4>

            <?php if($alumni->pekerjaans->isEmpty()): ?>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 12px; border: 1px dashed #cbd5e1; color: #94a3b8;">
                    Belum ada riwayat pekerjaan.
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php $__currentLoopData = $alumni->pekerjaans->sortByDesc('status_karir'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-left: 5px solid <?php echo e($job->status_karir == 'Utama' ? '#10b981' : ($job->status_karir == 'Sampingan' ? '#3b82f6' : '#94a3b8')); ?>;">
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 800;"><?php echo e($job->nama_perusahaan); ?></h5>
                                <span style="font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: bold; background: <?php echo e($job->status_karir == 'Utama' ? '#ecfdf5' : ($job->status_karir == 'Sampingan' ? '#eff6ff' : '#f8fafc')); ?>; color: <?php echo e($job->status_karir == 'Utama' ? '#10b981' : ($job->status_karir == 'Sampingan' ? '#3b82f6' : '#94a3b8')); ?>;">
                                    <?php echo e(strtoupper($job->status_karir)); ?>

                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                                <div><strong style="color: #64748b;">Jabatan:</strong> <?php echo e($job->jabatan); ?></div>
                                <div><strong style="color: #64748b;">Bidang:</strong> <?php echo e($job->bidang_pekerjaan); ?></div>
                                <div><strong style="color: #64748b;">Gaji:</strong> <?php echo e($job->gaji ?? 'Dirahasiakan'); ?></div>
                                <div><strong style="color: #64748b;">Kesesuaian:</strong> <span class="badge-<?php echo e($job->linearitas == 'Linier' ? 'linier' : 'tidak'); ?>" style="font-size:10px;"><?php echo e($job->linearitas); ?></span></div>
                                <div style="grid-column: span 2;"><strong style="color: #64748b;">Lokasi:</strong> <?php echo e($job->alamat_lengkap); ?> (<?php echo e($job->kota); ?>)</div>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div><?php /**PATH D:\Aplikasi_Skripsi\gis-alumni\resources\views/admin/komponen/modal-profil.blade.php ENDPATH**/ ?>