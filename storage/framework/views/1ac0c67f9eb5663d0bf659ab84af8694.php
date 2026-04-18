<style>
.profil-modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.65);
    z-index:9999;
    justify-content:center;
    align-items:center;
    padding:20px;
}

.profil-modal-card{
    width:100%;
    max-width:760px;
    max-height:92vh;
    overflow:hidden;
    border-radius:24px;
    background:#fff;
    display:flex;
    flex-direction:column;
    box-shadow:0 20px 60px rgba(0,0,0,.18);
    animation:fadeUp .25s ease;
    position:relative;
}

@keyframes fadeUp{
    from{opacity:0;transform:translateY(25px);}
    to{opacity:1;transform:translateY(0);}
}

.profil-close-btn{
    position:absolute;
    right:18px;
    top:18px;
    width:40px;
    height:40px;
    border:none;
    border-radius:50%;
    background:rgba(255,255,255,.2);
    color:#fff;
    font-size:22px;
    cursor:pointer;
    z-index:20;
}

.profil-header{
    background:linear-gradient(135deg,#004a87,#0a6dc2);
    padding:28px;
    display:flex;
    gap:18px;
    align-items:center;
}

.profil-avatar{
    width:92px;
    height:92px;
    border-radius:50%;
    object-fit:cover;
    border:4px solid rgba(255,255,255,.9);
}

.profil-header-info h2{
    margin:0 0 10px;
    color:#fff;
    font-size:26px;
    font-weight:800;
}

.profil-badge-group{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.badge-light{
    padding:5px 10px;
    border-radius:999px;
    font-size:12px;
    color:#fff;
    background:rgba(255,255,255,.18);
}

.profil-body{
    padding:24px;
    overflow-y:auto;
    background:#f8fafc;
}

.profil-section{
    background:#fff;
    border-radius:18px;
    padding:20px;
    margin-bottom:18px;
}

.section-title{
    font-weight:800;
    font-size:16px;
    color:#004a87;
    margin-bottom:16px;
}

.between{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.badge-total{
    font-size:12px;
    background:#eef2f7;
    padding:5px 10px;
    border-radius:999px;
    color:#475569;
}

.profil-grid,
.career-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.info-item small,
.career-grid small{
    display:block;
    font-size:12px;
    color:#64748b;
    margin-bottom:5px;
}

.info-item span,
.career-grid span{
    color:#0f172a;
    font-size:14px;
    line-height:1.5;
}

.full{
    grid-column:span 2;
}

.muted{
    color:#64748b;
}

.career-list{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.career-card{
    border-radius:18px;
    padding:18px;
    border-left:5px solid #cbd5e1;
    background:#fff;
}

.career-card.utama{border-color:#10b981;}
.career-card.sampingan{border-color:#3b82f6;}
.career-card.riwayat{border-color:#94a3b8;}

.career-top{
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-bottom:14px;
}

.career-top h4{
    margin:0;
    font-size:17px;
    color:#0f172a;
}

.career-top p{
    margin:4px 0 0;
    color:#64748b;
    font-size:14px;
}

.status-badge{
    padding:6px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    height:fit-content;
}

.status-badge.utama{
    background:#ecfdf5;
    color:#10b981;
}

.status-badge.sampingan{
    background:#eff6ff;
    color:#2563eb;
}

.status-badge.riwayat{
    background:#f1f5f9;
    color:#64748b;
}

.empty-box{
    padding:18px;
    text-align:center;
    border:1px dashed #cbd5e1;
    border-radius:14px;
    color:#64748b;
}

@media(max-width:768px){
    .profil-grid,
    .career-grid{
        grid-template-columns:1fr;
    }

    .full{
        grid-column:span 1;
    }

    .profil-header{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>
<div id="modal-profil-<?php echo e($alumni->id); ?>" class="profil-modal-overlay">

    <div class="profil-modal-card">

        
        <button type="button" class="profil-close-btn" onclick="closeProfilModal('<?php echo e($alumni->id); ?>')">
            &times;
        </button>


        
        <div class="profil-header">

            <img src="<?php echo e($alumni->foto_profil ? asset('storage/' . $alumni->foto_profil) : asset('images/default-user.png')); ?>"
                class="profil-avatar">

            <div class="profil-header-info">

                <h2><?php echo e($alumni->nama_lengkap); ?></h2>

                <div class="profil-badge-group">

                    <span class="badge-light">
                        NIM : <?php echo e($alumni->nim); ?>

                    </span>

                    <span class="badge-light">
                        Lulusan <?php echo e($alumni->akademik?->tahun_lulus ?? '-'); ?>

                    </span>

                    <span class="badge-light">
                        <?php echo e($alumni->akademik?->angkatan ?? '-'); ?>

                    </span>

                </div>

            </div>

        </div>



        
        <div class="profil-body">


            
            <div class="profil-section">

                <div class="section-title">
                    Informasi Pribadi
                </div>

                <div class="profil-grid">

                    <div class="info-item">
                        <small>Email</small>
                        <span><?php echo e($alumni->email ?? '-'); ?></span>
                    </div>

                    <div class="info-item">
                        <small>No WhatsApp</small>
                        <span><?php echo e($alumni->no_hp ?? '-'); ?></span>
                    </div>

                    <div class="info-item full">
                        <small>Judul Skripsi</small>
                        <span><?php echo e($alumni->akademik?->judul_skripsi ?? '-'); ?></span>
                    </div>

                    <div class="info-item full">
                        <small>Domisili Saat Ini</small>
                        <span>
                            <?php echo e($alumni->alamat?->kota ?? '-'); ?>

                            <br>
                            <small class="muted">
                                <?php echo e($alumni->alamat?->alamat_lengkap ?? '-'); ?>

                            </small>
                        </span>
                    </div>

                </div>

            </div>



            
            <div class="profil-section">

                <div class="section-title between">

                    <span>Riwayat Karir</span>

                    <span class="badge-total">
                        <?php echo e($alumni->pekerjaan->count()); ?> Data
                    </span>

                </div>


                <?php if($alumni->pekerjaan->isEmpty()): ?>

                    <div class="empty-box">
                        Belum ada riwayat pekerjaan
                    </div>
                <?php else: ?>
                    <div class="career-list">

                        <?php $__currentLoopData = $alumni->pekerjaan->sortByDesc('is_current'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $lokasi = $job->perusahaan?->lokasiUtama ?? $job->perusahaan?->lokasi->first();

                                $warna = match ($job->status_karir) {
                                    'Utama' => 'utama',
                                    'Sampingan' => 'sampingan',
                                    default => 'riwayat',
                                };

                                $linear = $job->perusahaan?->linearitas ?? '-';
                            ?>


                            <div class="career-card <?php echo e($warna); ?>">

                                <div class="career-top">

                                    <div>
                                        <h4>
                                            <?php echo e($job->perusahaan?->nama_perusahaan ?? '-'); ?>

                                        </h4>

                                        <p>
                                            <?php echo e($job->jabatan ?? '-'); ?>

                                        </p>
                                    </div>

                                    <span class="status-badge <?php echo e($warna); ?>">
                                        <?php echo e(strtoupper($job->status_karir)); ?>

                                    </span>

                                </div>


                                <div class="career-grid">

                                    <div>
                                        <small>Bidang</small>
                                        <span><?php echo e($job->bidang_pekerjaan ?? '-'); ?></span>
                                    </div>

                                    <div>
                                        <small>Linearitas</small>
                                        <span><?php echo e($linear); ?></span>
                                    </div>

                                    <div>
                                        <small>Gaji</small>
                                        <span>
                                            <?php echo e($job->gaji_nominal ? 'Rp ' . number_format($job->gaji_nominal, 0, ',', '.') : 'Dirahasiakan'); ?>

                                        </span>
                                    </div>

                                    <div>
                                        <small>Lokasi</small>
                                        <span>
                                            <?php echo e($lokasi?->kota ?? '-'); ?>

                                        </span>
                                    </div>

                                    <div class="full">
                                        <small>Alamat</small>
                                        <span>
                                            <?php echo e($lokasi?->alamat_lengkap ?? '-'); ?>

                                        </span>
                                    </div>

                                </div>

                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>




<script>
    function openProfilModal(id) {
        document.getElementById('modal-profil-' + id).style.display = 'flex';
    }

    function closeProfilModal(id) {
        document.getElementById('modal-profil-' + id).style.display = 'none';
    }
</script>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_3\resources\views/admin/komponen/modal-profil.blade.php ENDPATH**/ ?>