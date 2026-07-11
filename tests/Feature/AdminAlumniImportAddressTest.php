<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAlumniImportAddressTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('cache.default', 'array');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createImportSchema();

        $admin = new User([
            'name' => 'Admin Import Test',
            'email' => 'admin-import@example.test',
            'role' => 'admin',
            'password' => 'password',
        ]);
        $admin->id = 1;
        $admin->exists = true;

        $this->actingAs($admin);
    }

    public function test_import_stores_address_with_excel_coordinates_and_triggered_geom(): void
    {
        Http::preventStrayRequests();

        $response = $this->postImport([
            $this->baseRow('99000001') + [
                'alamat_lengkap_alumni' => 'Jl. Dummy Koordinat',
                'kota_kabupaten_alumni' => 'Banjarmasin',
                'provinsi_alumni' => 'Kalimantan Selatan',
                'latitude_alumni' => -3.3194,
                'longitude_alumni' => 114.5908,
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => 1,
            'skip' => 0,
            'failed' => 0,
            'no_map' => 0,
        ]);

        $alumni = Alumni::where('nim', '99000001')->firstOrFail();
        $alamat = DB::table('alamat_alumni')->where('alumni_id', $alumni->id)->first();

        $this->assertNotNull($alamat);
        $this->assertSame('Jl. Dummy Koordinat', $alamat->alamat_lengkap);
        $this->assertSame('Banjarmasin', $alamat->kota);
        $this->assertSame('Kalimantan Selatan', $alamat->provinsi);
        $this->assertEquals(-3.3194, (float) $alamat->latitude);
        $this->assertEquals(114.5908, (float) $alamat->longitude);
        $this->assertSame(1, (int) $alamat->is_current);
        $this->assertNotNull($alamat->geom);
    }

    public function test_import_stores_address_when_coordinates_are_unavailable(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $response = $this->postImport([
            $this->baseRow('99000002') + [
                'alamat_lengkap_alumni' => 'Jl. Dummy Tanpa Koordinat',
                'kota_kabupaten_alumni' => 'Banjarmasin',
                'provinsi_alumni' => 'Kalimantan Selatan',
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => 1,
            'failed' => 0,
            'no_map' => 1,
        ]);

        $alumni = Alumni::where('nim', '99000002')->firstOrFail();
        $alamat = DB::table('alamat_alumni')->where('alumni_id', $alumni->id)->first();

        $this->assertNotNull($alamat);
        $this->assertNull($alamat->latitude);
        $this->assertNull($alamat->longitude);
        $this->assertNull($alamat->geom);
        $this->assertSame(1, (int) $alamat->is_current);
    }

    public function test_empty_geocoding_result_does_not_fail_import_or_address_insert(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $response = $this->postImport([
            $this->baseRow('99000003') + [
                'alamat_lengkap_alumni' => 'Alamat Dummy Tidak Ditemukan Nominatim',
                'kota_kabupaten_alumni' => 'Kota Dummy',
                'provinsi_alumni' => 'Kalimantan Selatan',
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => 1,
            'skip' => 0,
            'failed' => 0,
            'no_map' => 1,
        ]);

        $alumni = Alumni::where('nim', '99000003')->firstOrFail();
        $alamat = DB::table('alamat_alumni')->where('alumni_id', $alumni->id)->first();

        $this->assertNotNull($alamat);
        $this->assertNull($alamat->latitude);
        $this->assertNull($alamat->longitude);
        $this->assertNull($alamat->geom);
    }

    public function test_import_does_not_create_an_empty_address_record(): void
    {
        Http::preventStrayRequests();

        $response = $this->postImport([
            $this->baseRow('99000004'),
        ]);

        $response->assertOk()->assertJson([
            'success' => 1,
            'failed' => 0,
            'no_map' => 1,
        ]);

        $alumni = Alumni::where('nim', '99000004')->firstOrFail();

        $this->assertDatabaseMissing('alamat_alumni', [
            'alumni_id' => $alumni->id,
        ]);
    }

    public function test_duplicate_nim_is_skipped_without_creating_an_address(): void
    {
        Http::preventStrayRequests();

        $existing = Alumni::create([
            'nim' => '99000005',
            'nama_lengkap' => 'Alumni Existing',
        ]);

        $response = $this->postImport([
            $this->baseRow('99000005') + [
                'alamat_lengkap_alumni' => 'Alamat Tidak Boleh Masuk',
                'latitude_alumni' => -3.3194,
                'longitude_alumni' => 114.5908,
            ],
        ]);

        $response->assertOk()->assertJson([
            'success' => 0,
            'skip' => 1,
            'failed' => 0,
            'no_map' => 0,
        ]);

        $this->assertDatabaseCount('alumnis', 1);
        $this->assertDatabaseMissing('alamat_alumni', [
            'alumni_id' => $existing->id,
        ]);
    }

    public function test_failed_address_insert_rolls_back_its_row_and_other_rows_continue(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $response = $this->postImport([
            $this->baseRow('99000006') + [
                'alamat_lengkap_alumni' => 'FORCE_ADDRESS_FAILURE',
            ],
            $this->baseRow('99000007') + [
                'alamat_lengkap_alumni' => 'Alamat Baris Berikutnya',
                'kota_kabupaten_alumni' => 'Banjarmasin',
                'provinsi_alumni' => 'Kalimantan Selatan',
                'latitude_alumni' => -3.3194,
                'longitude_alumni' => 114.5908,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => 1,
                'skip' => 0,
                'failed' => 1,
                'no_map' => 0,
            ])
            ->assertJsonCount(1, 'failed_rows');

        $this->assertDatabaseMissing('alumnis', ['nim' => '99000006']);
        $this->assertDatabaseCount('alumnis', 1);
        $this->assertDatabaseCount('alumni_akademik', 1);
        $this->assertDatabaseCount('alamat_alumni', 1);
        $this->assertDatabaseHas('alumnis', ['nim' => '99000007']);
    }

    public function test_address_insert_does_not_regress_job_company_location_or_study_import(): void
    {
        Http::preventStrayRequests();

        $response = $this->postImport([
            $this->baseRow('99000008') + [
                'alamat_lengkap_alumni' => 'Domisili Alumni Dummy',
                'kota_kabupaten_alumni' => 'Banjarmasin',
                'provinsi_alumni' => 'Kalimantan Selatan',
                'latitude_alumni' => -3.3194,
                'longitude_alumni' => 114.5908,
                'nama_perusahaan' => 'Perusahaan Dummy Import',
                'linearitas' => 'Erat',
                'alamat_lengkap_perusahaan' => 'Alamat Perusahaan Dummy',
                'kota_kabupaten_perusahaan' => 'Banjarbaru',
                'provinsi_perusahaan' => 'Kalimantan Selatan',
                'latitude_perusahaan' => -3.4400,
                'longitude_perusahaan' => 114.8300,
                'jabatan' => 'Tester',
                'bidang_pekerjaan' => 'Teknologi Informasi',
                'status_kerja' => 'Bekerja',
                'status_karir' => 'Utama',
                'kampus_studi_lanjut' => 'Kampus Dummy',
                'program_studi_lanjut' => 'Program Dummy',
                'alamat_kampus' => 'Alamat Kampus Dummy',
                'kota_kampus' => 'Banjarmasin',
                'provinsi_kampus' => 'Kalimantan Selatan',
                'latitude_kampus' => -3.3000,
                'longitude_kampus' => 114.6000,
                'jenjang' => 'S2',
                'status_studi_lanjut' => 'Sedang Berjalan',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => 1,
                'skip' => 0,
                'failed' => 0,
                'no_map' => 0,
            ])
            ->assertJsonStructure([
                'success',
                'skip',
                'failed',
                'no_map',
                'failed_rows',
            ]);

        $alumni = Alumni::where('nim', '99000008')->firstOrFail();

        $this->assertDatabaseHas('alamat_alumni', ['alumni_id' => $alumni->id]);
        $this->assertDatabaseHas('perusahaan', ['nama_perusahaan' => 'Perusahaan Dummy Import']);
        $this->assertDatabaseHas('lokasi_perusahaan', ['kota' => 'Banjarbaru']);
        $this->assertDatabaseHas('riwayat_pekerjaan', [
            'alumni_id' => $alumni->id,
            'jabatan' => 'Tester',
            'status_karir' => 'Utama',
        ]);
        $this->assertDatabaseHas('studi_lanjut', [
            'alumni_id' => $alumni->id,
            'kampus' => 'Kampus Dummy',
            'jenjang' => 'S2',
        ]);
    }

    private function postImport(array $rows)
    {
        return $this->postJson(route('admin.alumni.import.store'), [
            'rows' => $rows,
        ]);
    }

    private function baseRow(string $nim): array
    {
        return [
            'nim' => $nim,
            'nama_lengkap' => 'Alumni Dummy ' . $nim,
            'jenis_kelamin' => 'L',
            'angkatan' => 2020,
            'tahun_lulus' => 2024,
        ];
    }

    private function createImportSchema(): void
    {
        Schema::create('alumnis', function (Blueprint $table) {
            $table->id();
            $table->string('nim')->unique();
            $table->string('nama_lengkap');
            $table->char('jenis_kelamin', 1)->nullable();
            $table->string('email')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('foto_profil')->nullable();
            $table->timestamps();
        });

        Schema::create('alumni_akademik', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alumni_id');
            $table->integer('angkatan')->nullable();
            $table->integer('tahun_lulus')->nullable();
            $table->integer('tahun_yudisium')->nullable();
            $table->text('judul_skripsi')->nullable();
            $table->decimal('ipk', 3, 2)->nullable();
            $table->integer('nilai_toefl')->nullable();
            $table->integer('lama_studi')->nullable();
            $table->timestamps();
        });

        Schema::create('alamat_alumni', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alumni_id');
            $table->text('alamat_lengkap')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_current')->default(true);
            $table->string('geom')->nullable();
            $table->timestamps();
        });

        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan');
            $table->string('tingkat_instansi')->nullable();
            $table->string('linearitas')->nullable();
            $table->string('link_linkedin')->nullable();
            $table->timestamps();
        });

        Schema::create('lokasi_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perusahaan_id');
            $table->text('alamat_lengkap')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });

        Schema::create('riwayat_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alumni_id');
            $table->unsignedBigInteger('perusahaan_id')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('bidang_pekerjaan')->nullable();
            $table->string('status_kerja')->nullable();
            $table->boolean('is_current')->default(false);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->integer('masa_tunggu')->nullable();
            $table->string('status_karir')->nullable();
            $table->bigInteger('gaji_nominal')->nullable();
            $table->timestamps();
        });

        Schema::create('studi_lanjut', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alumni_id');
            $table->string('kampus')->nullable();
            $table->text('alamat_kampus')->nullable();
            $table->string('kota_kampus')->nullable();
            $table->string('provinsi_kampus')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('jenjang')->nullable();
            $table->string('program_studi')->nullable();
            $table->integer('tahun_masuk')->nullable();
            $table->integer('tahun_lulus')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        DB::unprepared("
            CREATE TRIGGER alamat_alumni_geom_sync
            AFTER INSERT ON alamat_alumni
            WHEN NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL
            BEGIN
                UPDATE alamat_alumni
                SET geom = 'POINT(' || NEW.longitude || ' ' || NEW.latitude || ')'
                WHERE id = NEW.id;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER force_address_insert_failure
            BEFORE INSERT ON alamat_alumni
            WHEN NEW.alamat_lengkap = 'FORCE_ADDRESS_FAILURE'
            BEGIN
                SELECT RAISE(ABORT, 'forced address insert failure');
            END
        ");
    }
}
