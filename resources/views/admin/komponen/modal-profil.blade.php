<div id="modal-profil-{{ $alumni->nim }}" class="profil-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    
    <div class="profil-modal-card" style="background: white; width: 100%; max-width: 700px; border-radius: 20px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; position: relative;">
        
        <button onclick="document.getElementById('modal-profil-{{ $alumni->nim }}').style.display='none'" style="position: absolute; right: 20px; top: 20px; background: rgba(255,255,255,0.2); border: none; font-size: 20px; color: white; cursor: pointer; z-index: 10;">&times;</button>

        <div style="background: linear-gradient(135deg, #004a87, #006bbf); padding: 30px; display: flex; gap: 20px; align-items: center;">
            <img src="{{ $alumni->foto_profil ? asset('storage/' . $alumni->foto_profil) : '/default.png' }}" 
                 style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid white; object-fit: cover;">
            <div style="color: white;">
                <h2 style="margin: 0 0 5px 0; font-size: 24px; font-weight: 800;">{{ $alumni->nama_lengkap }}</h2>
                <div style="display: flex; gap: 10px; font-size: 13px; opacity: 0.9;">
                    <span style="background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">NIM: {{ $alumni->nim }}</span>
                    <span style="background: rgba(0,0,0,0.2); padding: 4px 10px; border-radius: 20px;">Lulusan: {{ $alumni->tahun_lulus }}</span>
                </div>
            </div>
        </div>

        <div style="padding: 25px; overflow-y: auto; background: #f8fafc;">
            
            <div style="background: white; padding: 20px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 25px;">
                <h4 style="color: #004a87; margin: 0 0 15px 0; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Informasi Pribadi</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <small style="color: #64748b; font-weight: bold; display: block;">Email</small>
                        <span style="color: #1e293b; font-size: 14px;">{{ $alumni->email ?? '-' }}</span>
                    </div>
                    <div>
                        <small style="color: #64748b; font-weight: bold; display: block;">No. WhatsApp</small>
                        <span style="color: #1e293b; font-size: 14px;">{{ $alumni->no_hp ?? '-' }}</span>
                    </div>
                    <div style="grid-column: span 2;">
                        <small style="color: #64748b; font-weight: bold; display: block;">Judul Skripsi</small>
                        <p style="margin: 5px 0 0; color: #1e293b; font-size: 14px; font-style: italic; line-height: 1.5;">"{{ $alumni->judul_skripsi ?? 'Belum ada data' }}"</p>
                    </div>
                </div>
            </div>

            <h4 style="color: #004a87; margin: 0 0 15px 0; font-size: 16px; display: flex; align-items: center; justify-content: space-between;">
                Riwayat Pekerjaan
                <span style="font-size: 12px; background: #e2e8f0; color: #475569; padding: 3px 8px; border-radius: 6px;">{{ $alumni->pekerjaans->count() }} Data</span>
            </h4>

            @if($alumni->pekerjaans->isEmpty())
                <div style="text-align: center; padding: 20px; background: white; border-radius: 12px; border: 1px dashed #cbd5e1; color: #94a3b8;">
                    Belum ada riwayat pekerjaan.
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    @foreach($alumni->pekerjaans->sortByDesc('status_karir') as $job)
                        <div style="background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); border-left: 5px solid {{ $job->status_karir == 'Utama' ? '#10b981' : ($job->status_karir == 'Sampingan' ? '#3b82f6' : '#94a3b8') }};">
                            
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <h5 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 800;">{{ $job->nama_perusahaan }}</h5>
                                <span style="font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: bold; background: {{ $job->status_karir == 'Utama' ? '#ecfdf5' : ($job->status_karir == 'Sampingan' ? '#eff6ff' : '#f8fafc') }}; color: {{ $job->status_karir == 'Utama' ? '#10b981' : ($job->status_karir == 'Sampingan' ? '#3b82f6' : '#94a3b8') }};">
                                    {{ strtoupper($job->status_karir) }}
                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 13px;">
                                <div><strong style="color: #64748b;">Jabatan:</strong> {{ $job->jabatan }}</div>
                                <div><strong style="color: #64748b;">Bidang:</strong> {{ $job->bidang_pekerjaan }}</div>
                                <div><strong style="color: #64748b;">Gaji:</strong> {{ $job->gaji ?? 'Dirahasiakan' }}</div>
                                <div><strong style="color: #64748b;">Kesesuaian:</strong> <span class="badge-{{ $job->linearitas == 'Linier' ? 'linier' : 'tidak' }}" style="font-size:10px;">{{ $job->linearitas }}</span></div>
                                <div style="grid-column: span 2;"><strong style="color: #64748b;">Lokasi:</strong> {{ $job->alamat_lengkap }} ({{ $job->kota }})</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>