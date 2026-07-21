<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    // SIDANG-DB: Model menggunakan tabel alumnis sebagai data identitas utama alumni.
    protected $table = 'alumnis';

    // SIDANG-KEAMANAN: Hanya atribut identitas berikut yang dapat diisi secara massal.
    protected $fillable = [
        'nim',
        'nama_lengkap',
        'jenis_kelamin',
        'email',
        'no_hp',
        'foto_profil'
    ];

    // SIDANG-RELASI: Eloquent mengambil satu data akademik milik alumni; kardinalitas database mengikuti constraint migration.
    public function akademik()
    {
        return $this->hasOne(AlumniAkademik::class, 'alumni_id');
    }

    // SIDANG-RELASI: Relasi menyaring satu alamat alumni yang ditandai sebagai alamat aktif.
    public function alamat()
    {
        return $this->hasOne(AlamatAlumni::class, 'alumni_id')
                    ->whereRaw('is_current IS TRUE');
    }

    // SIDANG-RELASI: Satu alumni dapat memiliki banyak riwayat pekerjaan.
    public function pekerjaan()
    {
        return $this->hasMany(RiwayatPekerjaan::class, 'alumni_id');
    }

    // SIDANG-RELASI: Satu alumni dapat memiliki banyak riwayat studi lanjut.
    public function studiLanjut()
    {
        return $this->hasMany(StudiLanjut::class, 'alumni_id');
    }

    public function getDataCompletenessAttribute(): array
    {
        $dataDiri = $this->isPersonalDataComplete();
        $pekerjaanRequired = $this->hasWorkData();
        $pekerjaan = $pekerjaanRequired ? $this->isWorkDataComplete() : null;
        $studiRequired = $this->hasStudyData();
        $studiLanjut = $studiRequired ? $this->isStudyDataComplete() : null;

        $missing = [];
        if (!$dataDiri) {
            $missing[] = 'Data Diri';
        }
        if ($pekerjaanRequired && !$pekerjaan) {
            $missing[] = 'Pekerjaan';
        }
        if ($studiRequired && !$studiLanjut) {
            $missing[] = 'Studi Lanjut';
        }

        return [
            'is_complete' => empty($missing),
            'data_diri' => $dataDiri,
            'pekerjaan' => $pekerjaan,
            'pekerjaan_required' => $pekerjaanRequired,
            'studi_lanjut' => $studiLanjut,
            'studi_lanjut_required' => $studiRequired,
            'missing_fields' => $missing,
        ];
    }

    public function isDataComplete(): bool
    {
        return (bool) ($this->data_completeness['is_complete'] ?? false);
    }

    private function filledValue($value): bool
    {
        $text = trim((string) $value);

        return $text !== '' && $text !== '-' && strtolower($text) !== 'null';
    }

    private function isPersonalDataComplete(): bool
    {
        return $this->filledValue($this->nim)
            && $this->filledValue($this->nama_lengkap)
            && $this->filledValue($this->jenis_kelamin)
            && ($this->filledValue($this->email) || $this->filledValue($this->no_hp))
            && $this->alamat
            && $this->filledValue($this->alamat->alamat_lengkap)
            && $this->filledValue($this->alamat->kota)
            && $this->filledValue($this->alamat->provinsi)
            && $this->filledValue($this->alamat->latitude)
            && $this->filledValue($this->alamat->longitude);
    }

    private function hasWorkData(): bool
    {
        return $this->pekerjaan->contains(function ($job) {
            $status = strtolower(trim((string) $job->status_kerja));
            $statusKarir = strtolower(trim((string) $job->status_karir));
            $isNegativeWorkStatus = str_contains($status, 'belum') || str_contains($status, 'tidak');
            $isWorkingStatus = !$isNegativeWorkStatus && (str_contains($status, 'bekerja') || str_contains($status, 'kerja'));

            return $this->filledValue($job->jabatan)
                || $this->filledValue($job->bidang_pekerjaan)
                || $this->filledValue($job->perusahaan?->nama_perusahaan)
                || $job->is_current
                || $isWorkingStatus
                || str_contains($statusKarir, 'utama')
                || str_contains($statusKarir, 'sampingan');
        });
    }

    private function isWorkDataComplete(): bool
    {
        return $this->pekerjaan->contains(function ($job) {
            $lokasi = $job->perusahaan?->lokasiAktif ?: $job->perusahaan?->lokasi?->first();

            return $this->filledValue($job->perusahaan?->nama_perusahaan)
                && $this->filledValue($job->jabatan)
                && $this->filledValue($job->bidang_pekerjaan)
                && $this->filledValue($job->status_kerja)
                && $lokasi
                && $this->filledValue($lokasi->alamat_lengkap)
                && $this->filledValue($lokasi->kota)
                && $this->filledValue($lokasi->provinsi)
                && $this->filledValue($lokasi->latitude)
                && $this->filledValue($lokasi->longitude);
        });
    }

    private function hasStudyData(): bool
    {
        return $this->studiLanjut->isNotEmpty();
    }

    private function isStudyDataComplete(): bool
    {
        return $this->studiLanjut->contains(function ($study) {
            return $this->filledValue($study->kampus)
                && $this->filledValue($study->jenjang)
                && $this->filledValue($study->program_studi)
                && $this->filledValue($study->status)
                && $this->filledValue($study->kota_kampus)
                && $this->filledValue($study->provinsi_kampus)
                && $this->filledValue($study->latitude)
                && $this->filledValue($study->longitude);
        });
    }
}
