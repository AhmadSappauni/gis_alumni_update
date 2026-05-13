<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlumniImportTemplateExport implements FromArray, ShouldAutoSize, WithStyles
{
    public static function columns(): array
    {
        return [
            [
                'header' => 'nim',
                'stored_to' => 'alumnis.nim',
                'required' => true,
                'description' => 'Nomor induk mahasiswa. Baris tanpa NIM tidak diproses; NIM yang sudah ada akan dilewati.',
                'example' => '2010131210001',
            ],
            [
                'header' => 'nama_lengkap',
                'stored_to' => 'alumnis.nama_lengkap',
                'required' => true,
                'description' => 'Nama lengkap alumni. Jika kosong, sistem saat ini menyimpan tanda "-".',
                'example' => 'Ahmad Maulana',
            ],
            [
                'header' => 'jenis_kelamin',
                'stored_to' => 'alumnis.jenis_kelamin',
                'required' => false,
                'description' => 'Gunakan L, P, Laki-laki, atau Perempuan.',
                'example' => 'L',
            ],
            [
                'header' => 'email',
                'stored_to' => 'alumnis.email',
                'required' => false,
                'description' => 'Alamat email aktif alumni.',
                'example' => 'ahmad@example.com',
            ],
            [
                'header' => 'no_hp',
                'stored_to' => 'alumnis.no_hp',
                'required' => false,
                'description' => 'Nomor handphone alumni.',
                'example' => '081234567890',
            ],
            [
                'header' => 'angkatan',
                'stored_to' => 'alumni_akademik.angkatan',
                'required' => false,
                'description' => 'Tahun angkatan. Jika kosong, sistem mengambil 2 digit awal NIM.',
                'example' => '2020',
            ],
            [
                'header' => 'tahun_lulus',
                'stored_to' => 'alumni_akademik.tahun_lulus',
                'required' => false,
                'description' => 'Tahun lulus alumni.',
                'example' => '2024',
            ],
            [
                'header' => 'tahun_yudisium',
                'stored_to' => 'alumni_akademik.tahun_yudisium',
                'required' => false,
                'description' => 'Tahun yudisium alumni.',
                'example' => '2024',
            ],
            [
                'header' => 'nilai_toefl',
                'stored_to' => 'alumni_akademik.nilai_toefl',
                'required' => false,
                'description' => 'Nilai TOEFL dalam angka.',
                'example' => '475',
            ],
            [
                'header' => 'alamat_lengkap_alumni',
                'stored_to' => 'alamat_alumni.alamat_lengkap',
                'required' => false,
                'description' => 'Alamat domisili alumni saat ini.',
                'example' => 'Jl. A. Yani Km 36',
            ],
            [
                'header' => 'kota_kabupaten_alumni',
                'stored_to' => 'alamat_alumni.kota',
                'required' => false,
                'description' => 'Kota atau kabupaten domisili alumni.',
                'example' => 'Banjarbaru',
            ],
            [
                'header' => 'provinsi_alumni',
                'stored_to' => 'alamat_alumni.provinsi',
                'required' => false,
                'description' => 'Provinsi domisili alumni.',
                'example' => 'Kalimantan Selatan',
            ],
            [
                'header' => 'latitude_alumni',
                'stored_to' => 'alamat_alumni.latitude',
                'required' => false,
                'description' => 'Latitude domisili. Jika valid, dipakai langsung tanpa geocoding.',
                'example' => '-3.4421',
            ],
            [
                'header' => 'longitude_alumni',
                'stored_to' => 'alamat_alumni.longitude',
                'required' => false,
                'description' => 'Longitude domisili. Jika valid, dipakai langsung tanpa geocoding.',
                'example' => '114.8321',
            ],
            [
                'header' => 'nama_perusahaan',
                'stored_to' => 'perusahaan.nama_perusahaan',
                'required' => false,
                'description' => 'Nama instansi atau perusahaan tempat alumni bekerja.',
                'example' => 'PT Teknologi Banua',
            ],
            [
                'header' => 'linearitas',
                'stored_to' => 'perusahaan.linearitas',
                'required' => false,
                'description' => 'Tingkat keeratan pekerjaan dengan bidang pendidikan. Isi: Sangat Erat, Erat, Cukup Erat, Kurang Erat, atau Tidak Erat.',
                'example' => 'Sangat Erat',
            ],
            [
                'header' => 'alamat_lengkap_perusahaan',
                'stored_to' => 'lokasi_perusahaan.alamat_lengkap',
                'required' => false,
                'description' => 'Alamat kantor atau instansi.',
                'example' => 'Jl. Lambung Mangkurat No. 10',
            ],
            [
                'header' => 'kota_kabupaten_perusahaan',
                'stored_to' => 'lokasi_perusahaan.kota',
                'required' => false,
                'description' => 'Kota atau kabupaten lokasi kerja.',
                'example' => 'Banjarmasin',
            ],
            [
                'header' => 'provinsi_perusahaan',
                'stored_to' => 'lokasi_perusahaan.provinsi',
                'required' => false,
                'description' => 'Provinsi lokasi kerja.',
                'example' => 'Kalimantan Selatan',
            ],
            [
                'header' => 'latitude_perusahaan',
                'stored_to' => 'lokasi_perusahaan.latitude',
                'required' => false,
                'description' => 'Latitude lokasi kerja. Jika valid, dipakai langsung tanpa geocoding.',
                'example' => '-3.3194',
            ],
            [
                'header' => 'longitude_perusahaan',
                'stored_to' => 'lokasi_perusahaan.longitude',
                'required' => false,
                'description' => 'Longitude lokasi kerja. Jika valid, dipakai langsung tanpa geocoding.',
                'example' => '114.5908',
            ],
            [
                'header' => 'jabatan',
                'stored_to' => 'riwayat_pekerjaan.jabatan',
                'required' => false,
                'description' => 'Jabatan alumni di tempat kerja.',
                'example' => 'Guru Informatika',
            ],
            [
                'header' => 'bidang_pekerjaan',
                'stored_to' => 'riwayat_pekerjaan.bidang_pekerjaan',
                'required' => false,
                'description' => 'Bidang pekerjaan alumni.',
                'example' => 'Pendidikan',
            ],
            [
                'header' => 'status_kerja',
                'stored_to' => 'riwayat_pekerjaan.status_kerja',
                'required' => false,
                'description' => 'Isi Bekerja atau Belum Bekerja. Jika kosong tapi ada data pekerjaan, sistem menganggap Bekerja.',
                'example' => 'Bekerja',
            ],
            [
                'header' => 'tanggal_mulai',
                'stored_to' => 'riwayat_pekerjaan.tanggal_mulai',
                'required' => false,
                'description' => 'Tanggal mulai kerja. Format aman: YYYY-MM-DD.',
                'example' => '2024-08-01',
            ],
            [
                'header' => 'tanggal_selesai',
                'stored_to' => 'riwayat_pekerjaan.tanggal_selesai',
                'required' => false,
                'description' => 'Tanggal selesai kerja jika tidak lagi bekerja di sana.',
                'example' => '',
            ],
            [
                'header' => 'masa_tunggu',
                'stored_to' => 'riwayat_pekerjaan.masa_tunggu',
                'required' => false,
                'description' => 'Masa tunggu kerja dalam bulan.',
                'example' => '3',
            ],
            [
                'header' => 'status_karir',
                'stored_to' => 'riwayat_pekerjaan.status_karir',
                'required' => false,
                'description' => 'Status riwayat kerja, misalnya Utama atau Sampingan.',
                'example' => 'Utama',
            ],
            [
                'header' => 'gaji_nominal',
                'stored_to' => 'riwayat_pekerjaan.gaji_nominal',
                'required' => false,
                'description' => 'Nominal gaji dalam angka.',
                'example' => '3500000',
            ],
            [
                'header' => 'jenjang',
                'stored_to' => 'studi_lanjut.jenjang',
                'required' => false,
                'description' => 'Jenjang studi lanjut jika alumni melanjutkan pendidikan.',
                'example' => 'S2',
            ],
            [
                'header' => 'tahun_masuk_studi_lanjut',
                'stored_to' => 'studi_lanjut.tahun_masuk',
                'required' => false,
                'description' => 'Tahun masuk studi lanjut.',
                'example' => '2025',
            ],
            [
                'header' => 'tahun_lulus_studi_lanjut',
                'stored_to' => 'studi_lanjut.tahun_lulus',
                'required' => false,
                'description' => 'Tahun lulus studi lanjut jika sudah lulus.',
                'example' => '',
            ],
            [
                'header' => 'status_studi_lanjut',
                'stored_to' => 'studi_lanjut.status',
                'required' => false,
                'description' => 'Status studi lanjut.',
                'example' => 'Aktif',
            ],
        ];
    }

    public function array(): array
    {
        $columns = self::columns();

        return [
            array_column($columns, 'header'),
            array_column($columns, 'example'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$highestColumn}1")->getFill()
            ->setFillType('solid')
            ->getStartColor()
            ->setRGB('E0F2FE');
        $sheet->freezePane('A2');

        return [];
    }
}
