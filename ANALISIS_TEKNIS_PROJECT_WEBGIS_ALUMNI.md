# Analisis Teknis Project Web-GIS Alumni Pendidikan Komputer FKIP ULM

Dokumen ini disusun dari pembacaan langsung file project Laravel pada workspace `gis_alumni_5`. Isi di bawah bersifat faktual berdasarkan kode yang ditemukan. Jika suatu fitur tidak terlihat di kode aktif, dokumen ini menuliskannya sebagai "tidak ditemukan" atau "perlu verifikasi", bukan sebagai asumsi.

## 1. Gambaran Umum Aplikasi

Aplikasi ini adalah sistem Web-GIS dan tracer study alumni Pendidikan Komputer FKIP ULM. Fokus utamanya adalah menampilkan persebaran alumni berdasarkan status kerja, domisili, tempat kerja, dan studi lanjut, serta menyediakan dashboard statistik dan modul administrasi data alumni.

Halaman publik utama terdiri dari:

- Landing page di `/`, dikendalikan oleh `LandingController@index` dan route `routes/web.php:54`.
- Halaman peta interaktif di `/peta`, dikendalikan oleh `MapController@index` dan route `routes/web.php:18`.
- API data marker peta di `/map/data`, dikendalikan oleh `MapController@data` dan route `routes/web.php:19`.
- Dashboard statistik publik di `/statistik`, dikendalikan oleh `PublicStatistikController@index` dan route `routes/web.php:27`.
- API statistik publik di `/statistik/data`, route `routes/web.php:28`.

Halaman administrasi berada di prefix `/admin`, didefinisikan pada `routes/web.php:64`. Modul admin mencakup:

- Kelola data alumni: daftar, tambah, edit, update, hapus, bulk delete (`routes/web.php:74-93`).
- Cek NIM (`routes/web.php:102-103`).
- Import Excel/CSV dan template import (`routes/web.php:112-122`).
- Kelola riwayat pekerjaan (`routes/web.php:131-141`).
- Kelola studi lanjut (`routes/web.php:150-157`).
- Statistik admin dan ekspor laporan PDF/Excel (`routes/web.php:166-176`).
- Geocoding admin (`routes/web.php:66-67`).

Landing page menghitung ringkasan langsung dari database, antara lain total alumni (`app/Http/Controllers/LandingController.php:13`), jumlah wilayah terpetakan dengan `ST_Within` pada alamat dan lokasi perusahaan (`app/Http/Controllers/LandingController.php:19-33`), jumlah profil tracer yang memiliki pekerjaan atau studi lanjut (`app/Http/Controllers/LandingController.php:36-38`), lalu mengirim data ke `landing.index` (`app/Http/Controllers/LandingController.php:46-47`).

## 2. Arsitektur Teknologi

Backend menggunakan Laravel 12 dan PHP 8.2. Dependensi utama terlihat pada `composer.json`:

- PHP `^8.2` (`composer.json:9`).
- Laravel Framework `^12.0` (`composer.json:11`).
- `maatwebsite/excel` untuk import/export Excel (`composer.json:13`).
- `dompdf/dompdf` untuk ekspor PDF statistik (`composer.json:10`).
- PHPUnit untuk test (`composer.json:22`).

Frontend menggunakan asset statis Blade, CSS, dan JavaScript. Vite/Tailwind tersedia sebagai dev dependency:

- Script `vite build` dan `vite` (`package.json:5-7`).
- Tailwind, Vite, Axios, Laravel Vite Plugin (`package.json:10-15`).

Namun halaman peta dan admin juga memuat library dari CDN:

- Leaflet CSS/JS pada peta utama (`resources/views/index.blade.php:12`, `resources/views/index.blade.php:82`).
- Leaflet MarkerCluster (`resources/views/index.blade.php:13-14`, `resources/views/index.blade.php:83`).
- Leaflet Heat (`resources/views/index.blade.php:84`).
- Leaflet BetterScale (`resources/views/index.blade.php:15`, `resources/views/index.blade.php:85`).
- Leaflet MiniMap (`resources/views/index.blade.php:16`, `resources/views/index.blade.php:86`).
- Chart.js pada dashboard statistik publik (`resources/views/statistik/index.blade.php:444`) dan admin (`resources/views/admin/statistik/index.blade.php:482`).
- SweetAlert2 dan Bootstrap pada layout admin (`resources/views/admin/layout.blade.php:5-6`).

Database aktif diarahkan ke PostgreSQL. `.env` menunjukkan:

- `DB_CONNECTION=pgsql` (`.env:37`).
- `DB_HOST=127.0.0.1` (`.env:38`).
- `DB_PORT=5432` (`.env:39`).
- `DB_DATABASE=db_alumni_pilkom4` (`.env:40`).

Konfigurasi contoh juga memakai PostgreSQL (`.env.example:23-26`). File `config/database.php` menyediakan koneksi `pgsql` pada `config/database.php:86-99`.

Project juga memiliki konfigurasi deployment Vercel berbasis `vercel-php` di `vercel.json`, dengan route asset publik dan fallback ke `api/index.php`.

## 3. Struktur Database

Struktur data utama menggunakan beberapa tabel relasional:

### `alumnis`

Tabel `alumnis` dibuat pada `database/migrations/2026_04_17_025034_create_alumnis_table.php:14`. Kolom penting:

- `nim` unik (`database/migrations/2026_04_17_025034_create_alumnis_table.php:16`).
- `nama_lengkap` (`database/migrations/2026_04_17_025034_create_alumnis_table.php:17`).
- `jenis_kelamin`, `email`, `no_hp`, `foto_profil` (`database/migrations/2026_04_17_025034_create_alumnis_table.php:18-21`).

Modelnya adalah `App\Models\Alumni`, memakai tabel `alumnis` (`app/Models/Alumni.php:12`) dan fillable pada `app/Models/Alumni.php:14`.

### `alumni_akademik`

Tabel akademik dibuat pada `database/migrations/2026_04_17_025053_create_alumni_akademik_table.php:14`. Tabel ini memiliki foreign key ke `alumnis` dengan cascade delete (`database/migrations/2026_04_17_025053_create_alumni_akademik_table.php:16`). Kolom penting:

- `angkatan`, `tahun_lulus`, `tahun_yudisium` (`database/migrations/2026_04_17_025053_create_alumni_akademik_table.php:17-19`).
- `ipk`, `nilai_toefl`, `lama_studi` (`database/migrations/2026_04_17_025053_create_alumni_akademik_table.php:21-23`).

Model `AlumniAkademik` memakai tabel `alumni_akademik` (`app/Models/AlumniAkademik.php:12`) dan relasi `belongsTo` ke alumni (`app/Models/AlumniAkademik.php:27`).

### `alamat_alumni`

Tabel alamat alumni dibuat pada `database/migrations/2026_04_17_025108_create_alamat_alumni_table.php:14`. Tabel ini menyimpan domisili alumni, termasuk koordinat:

- Foreign key `alumni_id` cascade (`database/migrations/2026_04_17_025108_create_alamat_alumni_table.php:16`).
- `kota`, `provinsi` (`database/migrations/2026_04_17_025108_create_alamat_alumni_table.php:18-19`).
- `latitude`, `longitude` (`database/migrations/2026_04_17_025108_create_alamat_alumni_table.php:20-21`).
- `is_current` default true (`database/migrations/2026_04_17_025108_create_alamat_alumni_table.php:22`).

Model `AlamatAlumni` memakai tabel `alamat_alumni` (`app/Models/AlamatAlumni.php:12`), cast `is_current` sebagai boolean (`app/Models/AlamatAlumni.php:24`), dan relasi `belongsTo` ke alumni (`app/Models/AlamatAlumni.php:30`).

### `perusahaan`

Tabel perusahaan dibuat pada `database/migrations/2026_04_17_025128_create_perusahaans_table.php:14`. Kolom penting:

- `nama_perusahaan` (`database/migrations/2026_04_17_025128_create_perusahaans_table.php:16`).
- `tingkat_instansi`, `linearitas`, `link_linkedin` (`database/migrations/2026_04_17_025128_create_perusahaans_table.php:17-19`).

Catatan penting: method `down()` pada migrasi ini melakukan `Schema::dropIfExists('perusahaans')` (`database/migrations/2026_04_17_025128_create_perusahaans_table.php:29`), sedangkan tabel yang dibuat adalah `perusahaan`. Ini adalah inkonsistensi yang perlu dikoreksi jika rollback migrasi dipakai.

Model `Perusahaan` memakai tabel `perusahaan` (`app/Models/Perusahaan.php:12`), relasi ke pekerjaan (`app/Models/Perusahaan.php:23`), relasi ke lokasi (`app/Models/Perusahaan.php:27`), dan relasi `lokasiAktif()` sebagai lokasi terbaru (`app/Models/Perusahaan.php:30-33`).

### `lokasi_perusahaan`

Tabel lokasi perusahaan dibuat pada `database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:14`. Kolom penting:

- Foreign key `perusahaan_id` ke tabel `perusahaan` dengan cascade delete (`database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:17-19`).
- `kota`, `provinsi` (`database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:23-24`).
- `latitude`, `longitude` (`database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:26-27`).

Awalnya ada `nama_cabang` dan `is_head_office` (`database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:21`, `database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:29`), tetapi migrasi berikutnya menghapus kolom tersebut bila ada (`database/migrations/2026_04_25_000002_drop_head_office_and_branch_from_lokasi_perusahaan_table.php:28`).

Model `LokasiPerusahaan` memakai tabel `lokasi_perusahaan` (`app/Models/LokasiPerusahaan.php:9`), cast `latitude` dan `longitude` sebagai float (`app/Models/LokasiPerusahaan.php:20`), dan relasi ke perusahaan (`app/Models/LokasiPerusahaan.php:33`).

### `riwayat_pekerjaan`

Tabel riwayat pekerjaan dibuat pada `database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:14`. Kolom penting:

- Foreign key `alumni_id` cascade (`database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:16`).
- Foreign key `perusahaan_id` nullable dan `nullOnDelete` (`database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:17`).
- `jabatan`, `bidang_pekerjaan`, `status_kerja` (`database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:19-21`).
- `is_current`, `tanggal_mulai`, `tanggal_selesai` (`database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:22-25`).
- `masa_tunggu`, `status_karir`, `gaji_nominal` (`database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:27-30`).

Kolom `sumber_info` awalnya ada (`database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:29`) lalu dihapus oleh migrasi `2026_04_25_000001` (`database/migrations/2026_04_25_000001_drop_sumber_info_from_riwayat_pekerjaan_table.php:18`).

Model `RiwayatPekerjaan` memakai tabel `riwayat_pekerjaan` (`app/Models/RiwayatPekerjaan.php:12`), cast tanggal dan `is_current` (`app/Models/RiwayatPekerjaan.php:28`), relasi ke alumni (`app/Models/RiwayatPekerjaan.php:36`) dan perusahaan (`app/Models/RiwayatPekerjaan.php:41`).

### `studi_lanjut`

Tabel studi lanjut dibuat pada `database/migrations/2026_04_17_025156_create_studi_lanjut_table.php:14`. Kolom awal:

- Foreign key `alumni_id` cascade (`database/migrations/2026_04_17_025156_create_studi_lanjut_table.php:16`).
- `kampus`, `jenjang`, `program_studi` (`database/migrations/2026_04_17_025156_create_studi_lanjut_table.php:18-20`).
- `tahun_masuk`, `tahun_lulus`, `status` (`database/migrations/2026_04_17_025156_create_studi_lanjut_table.php:21-23`).

Migrasi berikutnya menambah data lokasi kampus:

- `alamat_kampus`, `kota_kampus`, `provinsi_kampus` (`database/migrations/2026_04_25_000003_add_location_fields_to_studi_lanjut_table.php:15-24`).
- `latitude`, `longitude` (`database/migrations/2026_04_25_000003_add_location_fields_to_studi_lanjut_table.php:28-32`).

Model `StudiLanjut` memakai tabel `studi_lanjut` (`app/Models/StudiLanjut.php:12`), fillable lokasi kampus (`app/Models/StudiLanjut.php:16-21`), cast koordinat sebagai float (`app/Models/StudiLanjut.php:29-31`), dan relasi ke alumni (`app/Models/StudiLanjut.php:36`).

### PostGIS dan indeks spasial

PostGIS diaktifkan oleh migrasi `2026_05_20_140632_enable_postgis_and_add_geom_columns.php`:

- `CREATE EXTENSION IF NOT EXISTS postgis` (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:11`).
- Kolom `geom geography(POINT, 4326)` pada `lokasi_perusahaan` (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:16`).
- Populate `geom` dari `longitude, latitude` menggunakan `ST_SetSRID(ST_MakePoint(...), 4326)::geography` (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:23`).
- Index GIST pada `lokasi_perusahaan.geom` (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:31-32`).
- Kolom `geom geography(POINT, 4326)` pada `alamat_alumni` (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:38`).
- Index GIST pada `alamat_alumni.geom` (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:48-49`).

Sinkronisasi otomatis `geom` dibuat dengan trigger:

- Function `update_geom_from_latlng()` (`database/migrations/2026_05_20_141430_add_geom_sync_triggers.php:11-15`).
- Trigger untuk `lokasi_perusahaan` (`database/migrations/2026_05_20_141430_add_geom_sync_triggers.php:23-26`).
- Trigger untuk `alamat_alumni` (`database/migrations/2026_05_20_141430_add_geom_sync_triggers.php:28-31`).

Tabel wilayah Kalimantan Selatan:

- `wilayah_kalsel` dibuat dengan `geom geometry(MultiPolygon, 4326)` (`database/migrations/2026_05_20_150421_create_wilayah_kalsel_table.php:11-16`).
- Index GIST pada `wilayah_kalsel.geom` (`database/migrations/2026_05_20_150421_create_wilayah_kalsel_table.php:22`).
- Seeder membaca `public/data/data_kalsel.geojson` dan memasukkan geometri memakai `ST_Multi(ST_Force2D(ST_GeomFromGeoJSON(?)))` (`database/seeders/WilayahKalselSeeder.php:55-56`).

Catatan: `studi_lanjut` memiliki `latitude` dan `longitude`, serta index biasa `idx_studi_coords` (`database/migrations/2026_05_17_000001_add_webgis_marker_indexes.php:53`), tetapi tidak ditemukan kolom `geom`/`geography` PostGIS atau trigger sync untuk tabel `studi_lanjut`.

## 4. Modul Manajemen Data Alumni

Modul manajemen alumni berada terutama pada `AdminAlumniController`.

### Daftar alumni admin

Method `index()` dimulai pada `app/Http/Controllers/AdminAlumniController.php:229`. Fitur yang ditemukan:

- Pagination dengan pilihan `per_page` (`app/Http/Controllers/AdminAlumniController.php:232`).
- Filter angkatan (`app/Http/Controllers/AdminAlumniController.php:241`).
- Filter tahun lulus (`app/Http/Controllers/AdminAlumniController.php:248`).
- Filter linearitas pekerjaan aktif (`app/Http/Controllers/AdminAlumniController.php:253-258`).
- Filter bidang pekerjaan (`app/Http/Controllers/AdminAlumniController.php:263-267`).
- Filter kelengkapan data diri, pekerjaan, studi lanjut, dan kelengkapan global (`app/Http/Controllers/AdminAlumniController.php:285-399`).
- Search nama/NIM dengan `ILIKE` untuk PostgreSQL (`app/Http/Controllers/AdminAlumniController.php:404-410`).
- Eager loading relasi akademik, alamat, pekerjaan, perusahaan, lokasi, dan studi lanjut (`app/Http/Controllers/AdminAlumniController.php:422-431`).
- Response AJAX berupa HTML komponen dan total data (`app/Http/Controllers/AdminAlumniController.php:436-440`).

View admin memakai layout `resources/views/admin/layout.blade.php` dan sidebar di `resources/views/admin/komponen/sidebar.blade.php`. Link menu admin tersedia untuk data alumni, tambah data/import, dan statistik (`resources/views/admin/komponen/sidebar.blade.php:8-35`).

### Tambah alumni

Route tambah dan simpan data berada pada:

- Form create: `routes/web.php:77-81`.
- Method `create()` mengembalikan view `admin.create` (`app/Http/Controllers/AdminAlumniController.php:477`).
- Method `store()` mulai pada `app/Http/Controllers/AdminAlumniController.php:602`.

Validasi awal `store()` mewajibkan NIM unik dan nama lengkap (`app/Http/Controllers/AdminAlumniController.php:604-606`). Penyimpanan dilakukan dalam transaksi database (`app/Http/Controllers/AdminAlumniController.php:609`).

Jika alumni ditandai belum bekerja, sistem membuat alamat domisili sebagai data lokasi utama (`app/Http/Controllers/AdminAlumniController.php:665-675`). Jika bekerja, sistem membuat atau memakai data perusahaan (`app/Http/Controllers/AdminAlumniController.php:689-698`), menyimpan lokasi perusahaan (`app/Http/Controllers/AdminAlumniController.php:705-712`), lalu membuat `RiwayatPekerjaan` aktif/utama (`app/Http/Controllers/AdminAlumniController.php:719-736`).

View `resources/views/admin/create.blade.php` memiliki input foto (`resources/views/admin/create.blade.php:168-175`), koordinat (`resources/views/admin/create.blade.php:293-297`), dan memuat `public/js/admin/create.js` (`resources/views/admin/create.blade.php:327`).

### Edit alumni

Route edit/update berada pada `routes/web.php:83-87`. Method `edit()` memuat alumni beserta relasi akademik, alamat, pekerjaan, perusahaan, lokasi, dan studi lanjut (`app/Http/Controllers/AdminAlumniController.php:487-536`), lalu mengembalikan view edit (`app/Http/Controllers/AdminAlumniController.php:547`).

Method `update()` dimulai pada `app/Http/Controllers/AdminAlumniController.php:744`. Pembaruan dilakukan dalam transaksi (`app/Http/Controllers/AdminAlumniController.php:748`), mencakup:

- Data dasar alumni (`app/Http/Controllers/AdminAlumniController.php:756-763`).
- Akademik dengan `updateOrCreate` (`app/Http/Controllers/AdminAlumniController.php:765-776`).
- Alamat aktif dengan `updateOrCreate` (`app/Http/Controllers/AdminAlumniController.php:778-790`).

Pekerjaan dan studi lanjut tidak diperbarui langsung lewat method `update()` utama, tetapi lewat endpoint khusus.

### Riwayat pekerjaan dan multi-job

Validasi pekerjaan berada di `validatePekerjaanRequest()` (`app/Http/Controllers/AdminAlumniController.php:154-168`). Field wajib meliputi nama perusahaan, jabatan, kota, bidang pekerjaan, linearitas, alamat, latitude, dan longitude.

Tambah pekerjaan:

- Route `POST /admin/alumni/{id}/pekerjaan` (`routes/web.php:131-132`).
- Method `storePekerjaan()` (`app/Http/Controllers/AdminAlumniController.php:1057`).
- Perusahaan dibuat dengan `firstOrCreate` (`app/Http/Controllers/AdminAlumniController.php:1068-1076`).
- Lokasi perusahaan dibuat (`app/Http/Controllers/AdminAlumniController.php:1083-1090`).
- Logika karier: jika pekerjaan current dan sudah ada pekerjaan utama, pekerjaan baru menjadi `Sampingan`; jika tidak current, menjadi `Riwayat` (`app/Http/Controllers/AdminAlumniController.php:1097-1119`).

Hapus pekerjaan:

- Method `destroyPekerjaan()` (`app/Http/Controllers/AdminAlumniController.php:1136`).
- Jika pekerjaan utama dihapus, sistem mempromosikan pekerjaan pengganti (`app/Http/Controllers/AdminAlumniController.php:1166-1182`).
- Perusahaan/lokasi dihapus jika sudah tidak dipakai (`app/Http/Controllers/AdminAlumniController.php:1191-1203`).

Update status pekerjaan:

- Method `updateStatusKerja()` mengubah semua pekerjaan alumni menjadi riwayat lalu menjadikan pekerjaan terpilih sebagai current/utama (`app/Http/Controllers/AdminAlumniController.php:1212-1225`).

Update pekerjaan:

- Method `updatePekerjaan()` (`app/Http/Controllers/AdminAlumniController.php:1228`).
- Update/firstOrCreate perusahaan (`app/Http/Controllers/AdminAlumniController.php:1250-1268`).
- Update atau buat lokasi terbaru (`app/Http/Controllers/AdminAlumniController.php:1275-1299`).
- Update data pekerjaan dan logika status karier (`app/Http/Controllers/AdminAlumniController.php:1306-1350`).

Form pekerjaan ada pada `resources/views/admin/komponen/riwayat-pekerjaan.blade.php`, termasuk form tambah (`resources/views/admin/komponen/riwayat-pekerjaan.blade.php:8-90`) dan modal edit (`resources/views/admin/komponen/riwayat-pekerjaan.blade.php:106-179`).

### Studi lanjut

Validasi studi lanjut berada pada `validateStudiLanjutRequest()` (`app/Http/Controllers/AdminAlumniController.php:172-199`). Field wajib: `kampus`, `jenjang`, `status`. Field lokasi kampus dan koordinat bersifat nullable.

Endpoint:

- Tambah: `routes/web.php:150-151`, method `storeStudiLanjut()` membuat record `StudiLanjut` (`app/Http/Controllers/AdminAlumniController.php:1370`).
- Update: `routes/web.php:153-154`, method `updateStudiLanjut()` (`app/Http/Controllers/AdminAlumniController.php:1378-1392`).
- Hapus: `routes/web.php:156-157`, method `destroyStudiLanjut()` (`app/Http/Controllers/AdminAlumniController.php:1395-1407`).

Form studi lanjut mendukung kampus, alamat kampus, kota, provinsi, jenjang, program studi, tahun, status, dan koordinat (`resources/views/admin/komponen/studi-lanjut.blade.php:14-90`).

### Upload foto

Upload foto memakai method `uploadFoto()` (`app/Http/Controllers/AdminAlumniController.php:558`). Sistem mencoba menggunakan Supabase bila `SUPABASE_URL`, `SUPABASE_KEY`, dan `SUPABASE_BUCKET` tersedia (`app/Http/Controllers/AdminAlumniController.php:564-566`). Jika gagal, fallback ke storage lokal disk `public` (`app/Http/Controllers/AdminAlumniController.php:591-596`).

### Kelengkapan data

Model `Alumni` memiliki accessor `getDataCompletenessAttribute()` (`app/Models/Alumni.php:44`) dan method `isDataComplete()` (`app/Models/Alumni.php:74`). Kelengkapan data diri, pekerjaan, dan studi lanjut dihitung dari field yang terisi (`app/Models/Alumni.php:86-152`). Ini digunakan oleh filter admin.

## 5. Modul Peta/Web-GIS

Modul peta menggunakan kombinasi backend `MapController`, Blade `resources/views/index.blade.php`, dan JavaScript pada `public/js/utama`.

### Backend payload peta

Method utama adalah `buildMapPayload()` (`app/Http/Controllers/MapController.php:38`). Payload berisi:

- Total alumni (`app/Http/Controllers/MapController.php:341`).
- Total bekerja (`app/Http/Controllers/MapController.php:342`).
- Total belum bekerja (`app/Http/Controllers/MapController.php:343`).
- Total multi-job (`app/Http/Controllers/MapController.php:344`).
- Total studi lanjut (`app/Http/Controllers/MapController.php:345`).
- Marker pekerjaan/domisisli (`app/Http/Controllers/MapController.php:346`).
- Marker studi lanjut (`app/Http/Controllers/MapController.php:347`).

Method `index()` mengembalikan view peta dengan `mapDataUrl` ke route `map.data` (`app/Http/Controllers/MapController.php:353-363`). Method `data()` mengembalikan JSON payload (`app/Http/Controllers/MapController.php:366-368`).

### Marker alumni bekerja

Data alumni bekerja diambil dari `RiwayatPekerjaan` aktif dengan status kerja bekerja (`app/Http/Controllers/MapController.php:436-456`). Jika satu alumni memiliki beberapa pekerjaan, `primaryCurrentJobs()` memilih pekerjaan prioritas berdasarkan status `Utama`, `tanggal_mulai`, `created_at`, dan `id` (`app/Http/Controllers/MapController.php:467-500`).

Lokasi marker alumni bekerja ditentukan oleh `resolveWorkingMarkerLocation()` (`app/Http/Controllers/MapController.php:503`). Logikanya:

- Menggunakan `perusahaan.lokasiAktif` jika tersedia (`app/Http/Controllers/MapController.php:511-512`).
- Jika lokasi perusahaan tidak punya koordinat, fallback ke alamat alumni (`app/Http/Controllers/MapController.php:519-521`).

Artinya, untuk alumni bekerja, posisi marker idealnya adalah lokasi kerja/perusahaan. Jika data lokasi kerja belum ada, marker memakai domisili alumni sebagai cadangan.

Payload marker bekerja memuat id alumni, NIM, nama, tahun lulus, angkatan, status `Bekerja`, koordinat, wilayah, alamat, perusahaan, jabatan, bidang, linearitas, dan indikator pekerjaan lain (`app/Http/Controllers/MapController.php:113-137`).

### Marker alumni belum bekerja

Alumni belum bekerja ditampilkan memakai alamat domisili. Query memastikan alumni memiliki alamat current dengan latitude dan longitude (`app/Http/Controllers/MapController.php:154-169`). Payload marker belum bekerja dibuat pada `app/Http/Controllers/MapController.php:179-203`.

### Marker studi lanjut

Marker studi lanjut berasal dari tabel `studi_lanjut`, khususnya lokasi kampus/universitas (`app/Http/Controllers/MapController.php:223-227`). Query mengambil kampus, alamat kampus, kota/provinsi kampus, koordinat, jenjang, program studi, tahun masuk/lulus, dan status (`app/Http/Controllers/MapController.php:227-242`). Query mensyaratkan latitude dan longitude tidak null (`app/Http/Controllers/MapController.php:251-252`).

Payload marker studi lanjut dibuat pada `app/Http/Controllers/MapController.php:269-287`.

### Frontend peta

View utama peta `resources/views/index.blade.php` memuat:

- CSS Leaflet, MarkerCluster, BetterScale, MiniMap, dan CSS aplikasi (`resources/views/index.blade.php:12-17`).
- Kontainer `#map`, legenda, sidebar, daftar alumni, id-card, dan kontrol cluster (`resources/views/index.blade.php:21-80`).
- JS Leaflet, MarkerCluster, Leaflet Heat, BetterScale, MiniMap (`resources/views/index.blade.php:82-86`).
- Variabel `window.mapDataUrl`, `window.mapPayload`, `alumniData`, `window.studiLanjutData` (`resources/views/index.blade.php:87-92`).
- JS aplikasi peta: `map.js`, `filter.js`, `sidebar.js`, `daftar-alumni.js`, `id-card.js`, `cluster.js` (`resources/views/index.blade.php:93-98`).

`public/js/utama/map.js` membuat peta Leaflet pada koordinat Kalimantan Selatan `[-3.316694, 114.590111]` (`public/js/utama/map.js:2-7`), menambahkan tile OpenStreetMap (`public/js/utama/map.js:130-132`), BetterScale/MiniMap (`public/js/utama/map.js:81-128`), polygon wilayah dari `public/data/data_kalsel.geojson` (`public/js/utama/map.js:897-901`), dan style choropleth (`public/js/utama/map.js:145-280`).

`public/js/utama/filter.js` mengatur mode visualisasi:

- Normalisasi mode marker/choropleth/heatmap (`public/js/utama/filter.js:16-21`).
- Membuat dan memperbarui heatmap dengan `L.heatLayer` (`public/js/utama/filter.js:144-188`).
- Mengubah mode visualisasi (`public/js/utama/filter.js:191-250`).
- Membuat layer marker normal/cluster untuk alumni dan studi lanjut (`public/js/utama/filter.js:317-359`).
- Fetch payload marker dari backend (`public/js/utama/filter.js:499-563`).

Mode visualisasi yang tersedia di UI adalah marker, choropleth, dan heatmap (`resources/views/utama/cluster.blade.php:13-20`).

## 6. Modul Filter dan Pencarian

### Filter peta publik

Panel filter peta berada di `resources/views/utama/filter-panel.blade.php`. Field yang tersedia:

- Search keyword (`resources/views/utama/filter-panel.blade.php:46`).
- Scope pencarian: semua, nama, perusahaan, wilayah (`resources/views/utama/filter-panel.blade.php:86-93`).
- Bidang pekerjaan (`resources/views/utama/filter-panel.blade.php:96-100`).
- Wilayah (`resources/views/utama/filter-panel.blade.php:103-107`).
- Tahun lulus relatif (`resources/views/utama/filter-panel.blade.php:110-118`).
- Linearitas (`resources/views/utama/filter-panel.blade.php:130-138`).
- Status kerja multi-select: bekerja, belum bekerja, studi lanjut (`resources/views/utama/filter-panel.blade.php:141-151`).
- Angkatan (`resources/views/utama/filter-panel.blade.php:154-158`).
- Tombol reset (`resources/views/utama/filter-panel.blade.php:164-167`).

Backend membaca parameter filter lewat `getMapFilters()` (`app/Http/Controllers/MapController.php:554`). Parameter yang diproses meliputi `search`, `search_scope`, `linearitas`, `bidang_pekerjaan`, `status`, `tahun`, `angkatan`, dan `wilayah_id` (`app/Http/Controllers/MapController.php:573-597`).

Frontend membangun query API melalui `buildMapMarkerApiParams()` (`public/js/utama/filter.js:390-427`) dan mengambil data dengan `fetchMapMarkersAndRender()` (`public/js/utama/filter.js:499-563`).

### Filter spasial wilayah pada peta

Untuk alumni bekerja, filter `wilayah_id` memakai PostGIS:

- `ST_Within(lokasi_perusahaan.geom::geometry, (SELECT geom FROM wilayah_kalsel WHERE id = ?))` (`app/Http/Controllers/MapController.php:650`).

Untuk alumni belum bekerja, filter `wilayah_id` juga memakai PostGIS:

- `ST_Within(alamat_alumni.geom::geometry, (SELECT geom FROM wilayah_kalsel WHERE id = ?))` (`app/Http/Controllers/MapController.php:708`).

Untuk studi lanjut, method `applyStudiLanjutQueryFilters()` ditemukan pada `app/Http/Controllers/MapController.php:726`, tetapi dari kode yang terlihat, filter utamanya berkaitan dengan akademik/keyword/tahun/angkatan. Tidak ditemukan filter PostGIS `wilayah_id` khusus untuk `studi_lanjut` di backend. Karena `studi_lanjut` juga tidak memiliki kolom `geom`, ini perlu dicatat sebagai keterbatasan jika artikel membahas filter polygon pada marker studi lanjut.

### Search dan filter admin

Daftar admin memakai AJAX live search pada `public/js/admin/filter-data.js`. Fitur yang terlihat:

- Fetch HTML hasil filter/pagination (`public/js/admin/filter-data.js:64-112`).
- Trigger search dengan debounce (`public/js/admin/filter-data.js:124-149`).
- Integrasi pagination AJAX dan `per_page` (`public/js/admin/filter-data.js:173-194`).

Backend admin memakai `ILIKE` untuk PostgreSQL (`app/Http/Controllers/AdminAlumniController.php:409-410`).

### Direktori alumni pada peta

Daftar alumni publik memakai `public/js/utama/daftar-alumni.js`:

- Membuka modal direktori dari tombol/sidebar (`public/js/utama/daftar-alumni.js:15-35`).
- Search alumni berdasarkan nama/NIM dari `alumniData` (`public/js/utama/daftar-alumni.js:45-68`).
- Render bertahap/chunked (`public/js/utama/daftar-alumni.js:78-138`).
- Tombol "lihat lokasi" menutup modal, reset filter, dan fly-to marker (`public/js/utama/daftar-alumni.js:156-174`).

## 7. Modul Dashboard, Statistik, dan Tracer Study

Dashboard statistik memiliki versi admin dan publik.

### Route dan controller

Admin:

- `/admin/statistik` (`routes/web.php:166-167`).
- `/admin/statistik/data` (`routes/web.php:169-170`).
- `/admin/statistik/export/pdf` (`routes/web.php:172-173`).
- `/admin/statistik/export/excel` (`routes/web.php:175-176`).

Publik:

- `/statistik` (`routes/web.php:27`).
- `/statistik/data` (`routes/web.php:28`).

`PublicStatistikController` mewarisi `StatistikController` dan hanya mengubah view index publik (`app/Http/Controllers/PublicStatistikController.php:9`). Method `data()` publik diwarisi dari parent.

### Filter statistik

`StatistikController` membaca filter `angkatan`, `tahun_lulus`, `jenis_kelamin`, `status_alumni`, `bidang_pekerjaan`, dan `wilayah_id` (`app/Http/Controllers/StatistikController.php:193-205`). Query utama dibangun di `buildFilteredAlumniQuery()` (`app/Http/Controllers/StatistikController.php:208`) dengan eager loading relasi akademik, alamat, pekerjaan/perusahaan/lokasi aktif, dan studi lanjut (`app/Http/Controllers/StatistikController.php:208-218`).

Filter wilayah statistik menggunakan `applyWilayahConnectionFilter()` (`app/Http/Controllers/StatistikController.php:241`). Alumni dianggap terkait wilayah jika:

- Lokasi kerja saat ini berada dalam polygon wilayah (`app/Http/Controllers/StatistikController.php:254`).
- Atau domisili alumni berada dalam polygon wilayah (`app/Http/Controllers/StatistikController.php:261`).

Komentar kode menegaskan bahwa `wilayah_id` sudah difilter di level SQL via `ST_Within` (`app/Http/Controllers/StatistikController.php:307`).

### KPI dan chart

Method `data()` dimulai pada `app/Http/Controllers/StatistikController.php:476`. Alur:

- Ambil filter (`app/Http/Controllers/StatistikController.php:478`).
- Query alumni (`app/Http/Controllers/StatistikController.php:483`).
- Filter hasil untuk statistik (`app/Http/Controllers/StatistikController.php:485`).
- Hitung top wilayah (`app/Http/Controllers/StatistikController.php:787`).
- Return JSON berisi `meta`, `kpis`, `charts`, dan `heatmaps` (`app/Http/Controllers/StatistikController.php:848-946`).

KPI yang dikirim meliputi total alumni, bekerja, belum bekerja, studi lanjut, multi-job, rata-rata masa tunggu, rata-rata TOEFL, dan jumlah TOEFL valid (`app/Http/Controllers/StatistikController.php:852-861`).

Chart yang dikirim meliputi:

- Status alumni, gender, linearitas, TOEFL, top bidang, top perusahaan, top wilayah, masa tunggu, jenjang studi, top kampus, tren angkatan, dan distribusi gaji (`app/Http/Controllers/StatistikController.php:862-930`).

Heatmap statistik dikirim sebagai `heatmaps.domisili` dan `heatmaps.lokasi_kerja` (`app/Http/Controllers/StatistikController.php:931-946`).

### View statistik

View admin statistik:

- Filter dashboard (`resources/views/admin/statistik/index.blade.php:24-104`).
- Tombol export PDF/Excel (`resources/views/admin/statistik/index.blade.php:15-19`).
- KPI cards (`resources/views/admin/statistik/index.blade.php:126-244`).
- Chart status, masa tunggu, gender, TOEFL, gaji, linearitas, top bidang, top perusahaan, top wilayah, top kampus, heatmap, tren alumni (`resources/views/admin/statistik/index.blade.php:249-477`).
- Chart.js dan JS admin statistik (`resources/views/admin/statistik/index.blade.php:482-488`).

View publik statistik:

- Memuat Chart.js (`resources/views/statistik/index.blade.php:444`).
- Mengatur endpoint `window.__STATISTIK_ENDPOINT__` ke route `statistik.data` dan memuat `js/utama/statistik.js` (`resources/views/statistik/index.blade.php:445-448`).

Frontend admin statistik `public/js/admin/statistik.js`:

- Membaca filter (`public/js/admin/statistik.js:23-31`).
- Build query (`public/js/admin/statistik.js:39-49`).
- Fetch payload (`public/js/admin/statistik.js:684-697`).
- Memetakan KPI dan chart dari payload (`public/js/admin/statistik.js:700-855`).
- Ekspor PDF/Excel dari URL endpoint (`public/js/admin/statistik.js:1370-1396`).
- Mengisi filter wilayah dari `/wilayah-kalsel` (`public/js/admin/statistik.js:1398-1423`).

Frontend publik statistik `public/js/utama/statistik.js` memiliki struktur serupa, dengan Chart.js default pada `public/js/utama/statistik.js:178-201` dan update chart pada `public/js/utama/statistik.js:728-822`.

### Ekspor PDF dan Excel

Ekspor PDF:

- Method `exportPdf()` (`app/Http/Controllers/StatistikController.php:1084`).
- Mode data `valid/all` dibaca dari query `data_mode` (`app/Http/Controllers/StatistikController.php:1086`).
- Insight otomatis dibuat oleh `buildInsightsForReport()` (`app/Http/Controllers/StatistikController.php:1112`).
- Dompdf dicek dan dipakai (`app/Http/Controllers/StatistikController.php:1125-1135`).

Ekspor Excel:

- Method `exportExcel()` (`app/Http/Controllers/StatistikController.php:1154`).
- Membuat `StatistikAlumniExport` (`app/Http/Controllers/StatistikController.php:1199`).
- Download dengan `Excel::download()` (`app/Http/Controllers/StatistikController.php:1212`).

`StatistikAlumniExport` menggunakan multiple sheets (`app/Exports/StatistikAlumniExport.php:15`). Sheet yang dibuat:

- Ringkasan umum (`app/Exports/StatistikAlumniExport.php:32`).
- Ketenagakerjaan (`app/Exports/StatistikAlumniExport.php:40`).
- Profil relevansi (`app/Exports/StatistikAlumniExport.php:41`).
- Statistik gaji (`app/Exports/StatistikAlumniExport.php:42`).
- Tren alumni (`app/Exports/StatistikAlumniExport.php:43`).
- Kualitas data (`app/Exports/StatistikAlumniExport.php:44`).
- Data alumni detail (`app/Exports/StatistikAlumniExport.php:45`).

Catatan validasi: `DataAlumniDetailSheet` mengambil kampus studi lanjut dengan properti `nama_kampus` (`app/Exports/Sheets/DataAlumniDetailSheet.php:123`), sedangkan model dan migrasi memakai kolom `kampus` (`app/Models/StudiLanjut.php:16`, `database/migrations/2026_04_17_025156_create_studi_lanjut_table.php:18`). Ini kemungkinan menyebabkan kolom kampus pada sheet detail selalu `-` dan perlu dikoreksi.

## 8. Modul Geocoding dan Data Spasial

### Nominatim proxy publik/admin

Project memiliki `NominatimController` untuk reverse geocode:

- Class controller `NominatimController` (`app/Http/Controllers/NominatimController.php:10`).
- Membaca email dan user agent dari config/env (`app/Http/Controllers/NominatimController.php:32-35`).
- Menyertakan `User-Agent` pada request (`app/Http/Controllers/NominatimController.php:52`).
- Route `/nominatim/reverse` (`routes/web.php:37`).

Config Nominatim:

- Base URL default `https://nominatim.openstreetmap.org` (`config/services.php:39`).
- Email (`config/services.php:40`).
- User agent (`config/services.php:41`).
- `.env.example` menyediakan `NOMINATIM_BASE_URL`, `NOMINATIM_EMAIL`, dan `NOMINATIM_USER_AGENT` (`.env.example:69-71`).

### Geocoding admin dan import

`AdminAlumniController` memiliki beberapa helper geocoding:

- `reverseGeocodeWilayah()` dengan cache 30 hari dan throttle sekitar 1 request/detik (`app/Http/Controllers/AdminAlumniController.php:35-64`).
- Endpoint `geocode()` untuk search/reverse Nominatim admin (`app/Http/Controllers/AdminAlumniController.php:1410-1434`).
- Normalisasi nama tempat seperti BJM, HST, HSU, HSS, Tanbu, Tala (`app/Http/Controllers/AdminAlumniController.php:2230-2263`).
- Build query geocoding (`app/Http/Controllers/AdminAlumniController.php:2269-2295` dan `app/Http/Controllers/AdminAlumniController.php:2347-2375`).
- Parsing koordinat dari format angka/koma (`app/Http/Controllers/AdminAlumniController.php:2377-2405`).
- Validasi koordinat global, Indonesia, dan Kalimantan Selatan bila hint provinsi tersedia (`app/Http/Controllers/AdminAlumniController.php:2437-2465`).
- `geocodeIfPossible()` dengan cache runtime, throttle, `User-Agent`, dan `countrycodes=id` (`app/Http/Controllers/AdminAlumniController.php:2483-2530`).
- Helper review akurasi geocoding digunakan saat fallback kurang spesifik (`app/Http/Controllers/AdminAlumniController.php:2408`, `app/Http/Controllers/AdminAlumniController.php:2580-2581`).

### PostGIS di query aplikasi

Query spasial utama menggunakan `ST_Within`, bukan `ST_DWithin`. Pencarian kode tidak menemukan penggunaan `ST_DWithin`.

Penggunaan `ST_Within` aktif:

- Landing page menghitung wilayah yang berisi alamat/lokasi kerja alumni (`app/Http/Controllers/LandingController.php:22`, `app/Http/Controllers/LandingController.php:30`).
- Filter wilayah peta untuk lokasi kerja (`app/Http/Controllers/MapController.php:650`).
- Filter wilayah peta untuk alamat alumni (`app/Http/Controllers/MapController.php:708`).
- Filter wilayah statistik (`app/Http/Controllers/StatistikController.php:254`, `app/Http/Controllers/StatistikController.php:261`).
- Top wilayah kerja statistik (`app/Http/Controllers/StatistikController.php:388`).

## 9. Autentikasi dan Hak Akses

Project memiliki model `User` default Laravel (`app/Models/User.php:20-45`) dan migrasi users default. `config/auth.php` juga ada sebagai konfigurasi bawaan Laravel.

Namun pada route aktif `routes/web.php`, prefix admin dibuat tanpa middleware `auth` (`routes/web.php:64`). Tidak ditemukan route login, register, logout, policy, gate, middleware role, atau package permission aktif pada hasil penelusuran. View `resources/views/welcome.blade.php` memang masih berisi referensi login/register bawaan Laravel (`resources/views/welcome.blade.php:24-48`), tetapi route aktif `/welcome` diarahkan ulang ke landing page (`routes/web.php:55`).

Kesimpulan faktual: autentikasi dan role admin tidak terlihat diterapkan pada route aktif. Jika aplikasi dipakai produksi, bagian ini perlu diamankan, misalnya dengan middleware `auth` pada group `/admin` dan role/permission sesuai kebutuhan.

Catatan lain: `StatistikController` memakai `auth()->user()?->name ?? 'Admin'` untuk nama pencetak laporan (`app/Http/Controllers/StatistikController.php:1119`, `app/Http/Controllers/StatistikController.php:1185`), tetapi route statistik admin sendiri tidak terlihat dilindungi middleware auth di `routes/web.php`.

## 10. Route Penting

Route publik:

- `GET /` -> landing page (`routes/web.php:54`).
- `GET /peta` -> peta interaktif (`routes/web.php:18`).
- `GET /map/data` -> JSON marker peta (`routes/web.php:19`).
- `GET /statistik` -> dashboard statistik publik (`routes/web.php:27`).
- `GET /statistik/data` -> JSON statistik publik (`routes/web.php:28`).
- `GET /nominatim` -> halaman utilitas Nominatim (`routes/web.php:36`).
- `GET /nominatim/reverse` -> reverse geocode (`routes/web.php:37`).
- `GET /wilayah-kalsel` -> daftar wilayah Kalsel (`routes/web.php:45`).

Route admin:

- `GET /admin/alumni` -> daftar alumni (`routes/web.php:74-75`).
- `GET /admin/alumni/create` -> form tambah alumni (`routes/web.php:77-78`).
- `POST /admin/alumni/store` -> simpan alumni (`routes/web.php:80-81`).
- `GET /admin/alumni/{id}/edit` -> form edit (`routes/web.php:83-84`).
- `PUT /admin/alumni/{id}` -> update alumni (`routes/web.php:86-87`).
- `DELETE /admin/alumni` -> bulk delete (`routes/web.php:89-90`).
- `DELETE /admin/alumni/{id}` -> hapus alumni (`routes/web.php:92-93`).
- `POST /admin/check-nim` -> cek duplikasi NIM (`routes/web.php:102-103`).
- `GET /admin/alumni/import` -> halaman import (`routes/web.php:112-113`).
- `GET /admin/alumni/import/template` -> download template import (`routes/web.php:115-116`).
- `POST /admin/alumni/import-preview` -> preview import (`routes/web.php:118-119`).
- `POST /admin/alumni/import-store` -> simpan import (`routes/web.php:121-122`).
- `POST /admin/alumni/{id}/pekerjaan` -> tambah pekerjaan (`routes/web.php:131-132`).
- `PUT /admin/pekerjaan/{id}/status` -> ubah status pekerjaan (`routes/web.php:134-135`).
- `PUT /admin/pekerjaan/{id}` -> update pekerjaan (`routes/web.php:137-138`).
- `DELETE /admin/pekerjaan/{id}` -> hapus pekerjaan (`routes/web.php:140-141`).
- `POST /admin/alumni/{alumni}/studi-lanjut` -> tambah studi lanjut (`routes/web.php:150-151`).
- `PUT /admin/alumni/{alumni}/studi-lanjut/{studiLanjut}` -> update studi lanjut (`routes/web.php:153-154`).
- `DELETE /admin/alumni/{alumni}/studi-lanjut/{studiLanjut}` -> hapus studi lanjut (`routes/web.php:156-157`).
- `GET /admin/statistik` -> dashboard statistik admin (`routes/web.php:166-167`).
- `GET /admin/statistik/data` -> JSON statistik admin (`routes/web.php:169-170`).
- `GET /admin/statistik/export/pdf` -> ekspor PDF (`routes/web.php:172-173`).
- `GET /admin/statistik/export/excel` -> ekspor Excel (`routes/web.php:175-176`).
- `GET /admin/geocode` -> geocoding admin (`routes/web.php:66-67`).

## 11. File Penting

Backend:

- `routes/web.php`: definisi route publik dan admin.
- `app/Http/Controllers/LandingController.php`: data ringkasan landing page.
- `app/Http/Controllers/MapController.php`: payload peta, filter marker, query PostGIS peta.
- `app/Http/Controllers/AdminAlumniController.php`: CRUD admin, import Excel/CSV, pekerjaan, studi lanjut, geocoding.
- `app/Http/Controllers/StatistikController.php`: statistik, KPI, chart, heatmap, ekspor PDF/Excel.
- `app/Http/Controllers/PublicStatistikController.php`: statistik publik.
- `app/Http/Controllers/NominatimController.php`: proxy reverse geocode.
- `app/Http/Controllers/WilayahController.php`: API daftar wilayah Kalsel.

Model:

- `app/Models/Alumni.php`: relasi utama dan kelengkapan data.
- `app/Models/AlumniAkademik.php`.
- `app/Models/AlamatAlumni.php`.
- `app/Models/Perusahaan.php`.
- `app/Models/LokasiPerusahaan.php`.
- `app/Models/RiwayatPekerjaan.php`.
- `app/Models/StudiLanjut.php`.
- `app/Models/User.php`.

View publik:

- `resources/views/landing/index.blade.php`: landing page dan mini map.
- `resources/views/index.blade.php`: halaman peta.
- `resources/views/utama/filter-panel.blade.php`: filter peta.
- `resources/views/utama/cluster.blade.php`: kontrol mode peta.
- `resources/views/utama/daftar-alumni.blade.php`: modal direktori alumni.
- `resources/views/utama/id-card.blade.php`: modal profil marker.
- `resources/views/statistik/index.blade.php`: statistik publik.

View admin:

- `resources/views/admin/layout.blade.php`: layout admin, SweetAlert, Bootstrap, Leaflet.
- `resources/views/admin/index.blade.php`: daftar alumni admin.
- `resources/views/admin/create.blade.php`: tambah alumni.
- `resources/views/admin/edit.blade.php`: edit alumni.
- `resources/views/admin/komponen/content.blade.php`: card/list alumni dan bulk delete.
- `resources/views/admin/komponen/riwayat-pekerjaan.blade.php`: form pekerjaan.
- `resources/views/admin/komponen/studi-lanjut.blade.php`: form studi lanjut.
- `resources/views/admin/import/import-excel.blade.php`: halaman import.
- `resources/views/admin/statistik/index.blade.php`: dashboard statistik admin.
- `resources/views/admin/statistik/pdf.blade.php`: template PDF statistik.

JavaScript:

- `public/js/utama/map.js`: inisialisasi Leaflet, polygon Kalsel, choropleth.
- `public/js/utama/filter.js`: filter peta, fetch marker, marker/cluster/heatmap.
- `public/js/utama/cluster.js`: toggle cluster, layer, kompas, legenda, minimap, polygon.
- `public/js/utama/daftar-alumni.js`: direktori alumni pada peta.
- `public/js/utama/id-card.js`: modal profil marker.
- `public/js/utama/statistik.js`: dashboard statistik publik.
- `public/js/admin/filter-data.js`: live search dan pagination admin.
- `public/js/admin/create.js`: wizard tambah alumni dan peta input lokasi.
- `public/js/admin/edit.js`: edit alumni, pekerjaan, studi lanjut, peta lokasi.
- `public/js/admin/import.js`: preview/import batch Excel/CSV.
- `public/js/admin/statistik.js`: dashboard statistik admin.

Database/spasial:

- `database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php`.
- `database/migrations/2026_05_20_141430_add_geom_sync_triggers.php`.
- `database/migrations/2026_05_20_150421_create_wilayah_kalsel_table.php`.
- `database/seeders/WilayahKalselSeeder.php`.
- `public/data/data_kalsel.geojson`.

Export/import:

- `app/Exports/AlumniImportTemplateExport.php`.
- `app/Exports/StatistikAlumniExport.php`.
- `app/Exports/Sheets/*`.
- `app/Imports/AlumniImport.php` ada, tetapi tampak sebagai kode lama/tidak aktif.

## 12. Fitur yang Sudah Selesai, Hampir Selesai, atau Belum Ada

### Terimplementasi kuat

- Landing page dengan KPI total alumni, wilayah terpetakan, profil tracer, dan cakupan angkatan (`app/Http/Controllers/LandingController.php:13-47`).
- Web-GIS publik berbasis Leaflet (`resources/views/index.blade.php:12-98`).
- Marker alumni bekerja, belum bekerja, dan studi lanjut (`app/Http/Controllers/MapController.php:113-203`, `app/Http/Controllers/MapController.php:269-287`).
- Mode marker, choropleth, dan heatmap pada peta (`resources/views/utama/cluster.blade.php:13-20`, `public/js/utama/filter.js:191-250`).
- Polygon wilayah Kalsel dari GeoJSON (`public/js/utama/map.js:897-901`).
- Filter peta berbasis backend API (`public/js/utama/filter.js:390-563`).
- Filter wilayah berbasis PostGIS untuk lokasi kerja dan domisili (`app/Http/Controllers/MapController.php:650`, `app/Http/Controllers/MapController.php:708`).
- Admin CRUD alumni (`routes/web.php:74-93`, `app/Http/Controllers/AdminAlumniController.php:229-802`).
- CRUD pekerjaan/multi-job (`routes/web.php:131-141`, `app/Http/Controllers/AdminAlumniController.php:1057-1354`).
- CRUD studi lanjut manual (`routes/web.php:150-157`, `app/Http/Controllers/AdminAlumniController.php:1363-1407`).
- Import Excel/CSV dengan preview dan batch import (`routes/web.php:112-122`, `public/js/admin/import.js:154-228`, `public/js/admin/import.js:304-448`).
- Template import Excel (`app/Exports/AlumniImportTemplateExport.php:12-270`, `app/Http/Controllers/AdminAlumniController.php:1444-1449`).
- Dashboard statistik publik dan admin (`app/Http/Controllers/StatistikController.php:476-947`).
- Ekspor statistik PDF dan Excel (`app/Http/Controllers/StatistikController.php:1084-1212`).
- PostGIS, kolom `geom`, trigger sync, index GIST (`database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:11-49`, `database/migrations/2026_05_20_141430_add_geom_sync_triggers.php:11-31`).

### Ada, tetapi perlu verifikasi atau perbaikan

- Proteksi admin: route admin belum memakai middleware `auth` (`routes/web.php:64`). Ini risiko keamanan.
- Import studi lanjut dari Excel hanya membaca `jenjang`, `tahun_masuk_studi_lanjut`, `tahun_lulus_studi_lanjut`, dan `status_studi_lanjut` (`app/Http/Controllers/AdminAlumniController.php:1700-1702`), lalu menyimpan `kampus`, alamat, kota/provinsi, koordinat, dan program studi sebagai `null` (`app/Http/Controllers/AdminAlumniController.php:2114-2124`). Padahal form manual studi lanjut mendukung field lengkap (`resources/views/admin/komponen/studi-lanjut.blade.php:14-90`).
- Sheet detail Excel statistik memakai properti `nama_kampus`, sementara kolom sebenarnya `kampus` (`app/Exports/Sheets/DataAlumniDetailSheet.php:123`, `app/Models/StudiLanjut.php:16`).
- Filter PostGIS `wilayah_id` tidak ditemukan untuk marker studi lanjut di backend. Studi lanjut memakai lat/lng biasa dan tidak punya `geom`.
- Migration `perusahaan` memiliki typo rollback: create `perusahaan`, drop `perusahaans` (`database/migrations/2026_04_17_025128_create_perusahaans_table.php:14`, `database/migrations/2026_04_17_025128_create_perusahaans_table.php:29`).
- `app/Imports/AlumniImport.php` tampak sebagai import lama: merujuk `App\Models\Pekerjaan` (`app/Imports/AlumniImport.php:6`) dan `Pekerjaan::create()` (`app/Imports/AlumniImport.php:26`), sedangkan kode aktif memakai `RiwayatPekerjaan`. Route aktif import tidak memanggil class ini, melainkan membaca Excel langsung di `AdminAlumniController`.
- Folder/file lama seperti `routes/web_old/web.php`, `app/Http/Controllers/controller_old`, `app/Models/model_old`, dan `database/migrations/backup_migration` ada di project. Artikel sebaiknya membahas kode aktif dan tidak menjadikan folder lama sebagai fitur aktif.

### Tidak ditemukan pada kode aktif

- Middleware autentikasi/role untuk admin.
- DataTables/Yajra DataTables.
- Google Maps API.
- `ST_DWithin`.
- Export Excel/PDF khusus daftar alumni selain ekspor statistik. Yang ditemukan adalah template import dan ekspor statistik.
- Kolom PostGIS `geom` pada `studi_lanjut`.
- Test khusus modul Web-GIS/tracer study. Yang ada hanya example test bawaan (`tests/Feature/ExampleTest.php:8-17`, `tests/Unit/ExampleTest.php:7-14`).

## 13. Kaitan dengan Artikel Ilmiah

Berdasarkan kode, topik artikel dapat dijelaskan sebagai pengembangan sistem Web-GIS tracer study alumni yang mengintegrasikan data akademik, domisili, riwayat pekerjaan, lokasi perusahaan, dan studi lanjut.

Aspek yang kuat untuk ditulis:

- Sistem berbasis web menggunakan Laravel 12 sebagai backend, Blade/JavaScript sebagai frontend, dan PostgreSQL/PostGIS sebagai basis data spasial.
- Data alumni dimodelkan secara relasional: data personal, akademik, alamat, perusahaan, lokasi perusahaan, riwayat pekerjaan, dan studi lanjut.
- Visualisasi spasial menggunakan Leaflet dengan tiga mode: marker, choropleth, dan heatmap.
- Lokasi kerja dan domisili alumni difilter berdasarkan batas administrasi kabupaten/kota Kalimantan Selatan menggunakan `ST_Within`.
- PostGIS digunakan untuk kolom geografi point (`geom`) pada alamat alumni dan lokasi perusahaan, serta polygon MultiPolygon untuk wilayah Kalsel.
- Data wilayah Kalsel diambil dari GeoJSON dan dimasukkan ke PostGIS dengan `ST_GeomFromGeoJSON`, `ST_Force2D`, dan `ST_Multi`.
- Dashboard tracer study menampilkan KPI dan chart status alumni, bidang pekerjaan, linearitas, masa tunggu, gaji, TOEFL, top perusahaan, wilayah kerja, studi lanjut, dan tren angkatan.
- Sistem menyediakan import data dari Excel/CSV dan ekspor laporan statistik ke PDF/Excel.

Narasi metodologi yang faktual:

- Pengembangan aplikasi dapat dijelaskan dengan model pengembangan perangkat lunak, tetapi istilah model pengembangan seperti waterfall/prototype/R&D tidak terbukti dari kode. Jika artikel menyebut model pengembangan, sumbernya harus berasal dari dokumen penelitian penulis, bukan dari kode.
- Pengujian yang terbukti di repo masih minimal/example test. Jika artikel mengklaim black-box testing, UAT, atau validasi ahli, bukti harus berasal dari dokumen penelitian terpisah, bukan dari repo ini.
- Akurasi geocoding perlu dijelaskan sebagai berbasis Nominatim/OpenStreetMap dan validasi koordinat, bukan sebagai geocoding resmi pemerintah atau Google Maps.

Variabel/indikator tracer study yang didukung kode:

- Status alumni: bekerja, belum bekerja, studi lanjut (`app/Http/Controllers/StatistikController.php:273-291`).
- Bidang pekerjaan (`riwayat_pekerjaan.bidang_pekerjaan`).
- Linearitas/kesesuaian bidang (`perusahaan.linearitas`).
- Masa tunggu kerja (`riwayat_pekerjaan.masa_tunggu`).
- Gaji nominal (`riwayat_pekerjaan.gaji_nominal`).
- Jenjang dan kampus studi lanjut (`studi_lanjut.jenjang`, `studi_lanjut.kampus`).
- Angkatan dan tahun lulus (`alumni_akademik.angkatan`, `alumni_akademik.tahun_lulus`).
- Nilai TOEFL (`alumni_akademik.nilai_toefl`).
- Domisili alumni dan lokasi kerja.

## 14. Bukti Kode Utama

Tabel ringkas bukti:

| Topik | Bukti file dan baris |
| --- | --- |
| Route peta | `routes/web.php:18-19` |
| Route statistik publik | `routes/web.php:27-28` |
| Route admin group | `routes/web.php:64` |
| Route admin CRUD alumni | `routes/web.php:74-93` |
| Route import | `routes/web.php:112-122` |
| Route pekerjaan | `routes/web.php:131-141` |
| Route studi lanjut | `routes/web.php:150-157` |
| Route ekspor statistik | `routes/web.php:172-176` |
| Model Alumni dan relasi | `app/Models/Alumni.php:23-41` |
| Kelengkapan data alumni | `app/Models/Alumni.php:44-152` |
| Tabel alumni | `database/migrations/2026_04_17_025034_create_alumnis_table.php:14-22` |
| Tabel akademik | `database/migrations/2026_04_17_025053_create_alumni_akademik_table.php:14-24` |
| Tabel alamat | `database/migrations/2026_04_17_025108_create_alamat_alumni_table.php:14-23` |
| Tabel perusahaan | `database/migrations/2026_04_17_025128_create_perusahaans_table.php:14-20` |
| Tabel riwayat pekerjaan | `database/migrations/2026_04_17_025144_create_riwayat_pekerjaan_table.php:14-32` |
| Tabel studi lanjut | `database/migrations/2026_04_17_025156_create_studi_lanjut_table.php:14-25` |
| Lokasi perusahaan | `database/migrations/2026_04_17_092518_create_lokasi_perusahaan_table.php:14-31` |
| PostGIS dan GIST | `database/migrations/2026_05_20_140632_enable_postgis_and_add_geom_columns.php:11-49` |
| Trigger geom | `database/migrations/2026_05_20_141430_add_geom_sync_triggers.php:11-31` |
| Polygon wilayah Kalsel | `database/migrations/2026_05_20_150421_create_wilayah_kalsel_table.php:11-24` |
| Seeder GeoJSON ke PostGIS | `database/seeders/WilayahKalselSeeder.php:55-56` |
| Payload peta | `app/Http/Controllers/MapController.php:38-347` |
| Filter peta | `app/Http/Controllers/MapController.php:554-597` |
| Filter wilayah lokasi kerja | `app/Http/Controllers/MapController.php:650` |
| Filter wilayah domisili | `app/Http/Controllers/MapController.php:708` |
| Halaman peta Leaflet | `resources/views/index.blade.php:12-98` |
| JS mode peta | `public/js/utama/filter.js:191-250` |
| GeoJSON polygon frontend | `public/js/utama/map.js:897-901` |
| Admin daftar alumni | `app/Http/Controllers/AdminAlumniController.php:229-472` |
| Admin store alumni | `app/Http/Controllers/AdminAlumniController.php:602-741` |
| Admin update alumni | `app/Http/Controllers/AdminAlumniController.php:744-795` |
| Admin bulk delete | `app/Http/Controllers/AdminAlumniController.php:993-1053` |
| Admin pekerjaan | `app/Http/Controllers/AdminAlumniController.php:1057-1354` |
| Admin studi lanjut | `app/Http/Controllers/AdminAlumniController.php:1363-1407` |
| Import preview/store | `app/Http/Controllers/AdminAlumniController.php:1499-2150` |
| Import JS batch | `public/js/admin/import.js:304-448` |
| Statistik filter/query | `app/Http/Controllers/StatistikController.php:193-268` |
| Statistik data JSON | `app/Http/Controllers/StatistikController.php:476-947` |
| Statistik PostGIS wilayah | `app/Http/Controllers/StatistikController.php:254-261` |
| Ekspor PDF | `app/Http/Controllers/StatistikController.php:1084-1151` |
| Ekspor Excel | `app/Http/Controllers/StatistikController.php:1154-1212` |
| Multi-sheet Excel | `app/Exports/StatistikAlumniExport.php:29-45` |
| Template import | `app/Exports/AlumniImportTemplateExport.php:12-270` |
| Nominatim config | `config/services.php:39-41` |
| Nominatim controller | `app/Http/Controllers/NominatimController.php:32-65` |
| Auth admin tidak ditemukan | `routes/web.php:64` tanpa middleware `auth` |

## 15. Ringkasan Akhir

1. Aplikasi ini adalah Web-GIS tracer study alumni dengan Laravel, PostgreSQL/PostGIS, Leaflet, Chart.js, dan Maatwebsite Excel.
2. Data utama dipisah menjadi alumni, akademik, alamat, perusahaan, lokasi perusahaan, riwayat pekerjaan, dan studi lanjut.
3. Peta publik menampilkan marker bekerja, belum bekerja, dan studi lanjut.
4. Marker bekerja memakai lokasi perusahaan sebagai prioritas dan fallback ke domisili jika lokasi kerja belum punya koordinat.
5. Marker belum bekerja memakai alamat domisili alumni.
6. Marker studi lanjut memakai koordinat kampus pada tabel `studi_lanjut`.
7. Filter wilayah untuk lokasi kerja dan domisili memakai PostGIS `ST_Within` terhadap polygon `wilayah_kalsel`.
8. Sistem memiliki mode marker, choropleth, dan heatmap pada peta utama.
9. Dashboard statistik menghitung KPI tracer study, chart status, bidang, linearitas, masa tunggu, gaji, TOEFL, wilayah, kampus, dan tren angkatan.
10. Admin dapat mengelola alumni, pekerjaan multi-job, studi lanjut, import data, dan ekspor laporan statistik.
11. Geocoding menggunakan Nominatim/OpenStreetMap, dengan throttle, cache, validasi koordinat, dan reverse geocoding wilayah.
12. Export statistik PDF memakai Dompdf; export Excel memakai multi-sheet `StatistikAlumniExport`.
13. Autentikasi/otorisasi admin tidak terlihat pada route aktif, sehingga perlu ditambahkan sebelum produksi.
14. Beberapa bagian perlu koreksi: rollback tabel `perusahaan`, import studi lanjut yang belum lengkap, dan properti `nama_kampus` pada sheet detail Excel.
15. Untuk artikel ilmiah, klaim yang paling kuat adalah pengembangan sistem Web-GIS berbasis PostGIS dan Leaflet untuk visualisasi persebaran alumni dan analisis tracer study berbasis data pekerjaan, domisili, akademik, dan studi lanjut.
