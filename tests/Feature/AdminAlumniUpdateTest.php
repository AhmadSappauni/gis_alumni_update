<?php

namespace Tests\Feature;

use App\Models\AlamatAlumni;
use App\Models\Alumni;
use App\Models\AlumniAkademik;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminAlumniUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

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
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        $admin = new User([
            'name' => 'Admin Test',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'password' => 'password',
        ]);
        $admin->id = 1;
        $admin->exists = true;

        $this->actingAs($admin);
    }

    public function test_edit_form_prefills_the_additional_academic_and_address_fields(): void
    {
        $alumni = $this->createAlumni('22000000');
        $alumni->load(['akademik', 'alamat']);
        $alumni->setRelation('pekerjaan', collect());
        $alumni->setRelation('studiLanjut', collect());

        $this->withViewErrors([]);
        $response = $this->view('admin.edit', compact('alumni'));

        $response
            ->assertSee('name="nilai_toefl" class="custom-input-admin" value="525"', false)
            ->assertSee('name="tahun_yudisium" class="custom-input-admin" value="2026"', false)
            ->assertSee('name="lama_studi" class="custom-input-admin" value="48"', false)
            ->assertSee('name="provinsi" class="custom-input-admin" value="Kalimantan Selatan"', false);
    }

    public function test_submitted_additional_fields_are_saved_without_regressing_other_profile_fields(): void
    {
        $alumni = $this->createAlumni('22000006');

        $response = $this->put(route('admin.alumni.update', $alumni->id), [
            'nim' => $alumni->nim,
            'nama_lengkap' => 'Nama Baru Lengkap',
            'jenis_kelamin' => 'P',
            'email' => 'baru@example.test',
            'no_hp' => '089876543210',
            'angkatan' => 2022,
            'tahun_lulus' => 2027,
            'tahun_yudisium' => 2027,
            'judul_skripsi' => 'Judul skripsi baru',
            'ipk' => 3.95,
            'nilai_toefl' => 600,
            'lama_studi' => 54,
            'alamat_tinggal' => 'Alamat domisili baru',
            'kota_tinggal' => 'Martapura',
            'provinsi' => 'Kalimantan Selatan Baru',
            'latitude_tinggal' => -3.4100,
            'longitude_tinggal' => 114.8500,
        ]);

        $response
            ->assertRedirect(route('admin.alumni.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alumnis', [
            'id' => $alumni->id,
            'nim' => '22000006',
            'nama_lengkap' => 'Nama Baru Lengkap',
            'jenis_kelamin' => 'P',
            'email' => 'baru@example.test',
            'no_hp' => '089876543210',
        ]);
        $this->assertDatabaseHas('alumni_akademik', [
            'alumni_id' => $alumni->id,
            'angkatan' => 2022,
            'tahun_lulus' => 2027,
            'tahun_yudisium' => 2027,
            'judul_skripsi' => 'Judul skripsi baru',
            'nilai_toefl' => 600,
            'lama_studi' => 54,
        ]);
        $this->assertDatabaseHas('alamat_alumni', [
            'alumni_id' => $alumni->id,
            'alamat_lengkap' => 'Alamat domisili baru',
            'kota' => 'Martapura',
            'provinsi' => 'Kalimantan Selatan Baru',
        ]);
    }

    public function test_omitted_additional_fields_keep_their_existing_values(): void
    {
        $alumni = $this->createAlumni('22000001');

        $response = $this->put(route('admin.alumni.update', $alumni->id), [
            'nim' => $alumni->nim,
            'nama_lengkap' => 'Nama Diperbarui',
            'jenis_kelamin' => 'L',
            'angkatan' => 2022,
            'tahun_lulus' => 2026,
            'judul_skripsi' => 'Judul baru',
            'ipk' => 3.90,
            'alamat_tinggal' => 'Alamat baru',
            'kota_tinggal' => 'Banjarbaru',
            'latitude_tinggal' => -3.4420,
            'longitude_tinggal' => 114.8320,
        ]);

        $response
            ->assertRedirect(route('admin.alumni.index'))
            ->assertSessionHas('success', 'Data alumni berhasil diupdate');

        $this->assertDatabaseHas('alumni_akademik', [
            'alumni_id' => $alumni->id,
            'tahun_yudisium' => 2026,
            'nilai_toefl' => 525,
            'lama_studi' => 48,
        ]);
        $this->assertDatabaseHas('alamat_alumni', [
            'alumni_id' => $alumni->id,
            'provinsi' => 'Kalimantan Selatan',
        ]);
    }

    public function test_unchanged_nim_is_accepted_for_the_same_alumni(): void
    {
        $alumni = $this->createAlumni('22000002');

        $response = $this->put(route('admin.alumni.update', $alumni->id), [
            'nim' => '22000002',
            'nama_lengkap' => 'Nama Tetap Valid',
            'jenis_kelamin' => 'P',
        ]);

        $response
            ->assertRedirect(route('admin.alumni.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('alumnis', [
            'id' => $alumni->id,
            'nim' => '22000002',
            'nama_lengkap' => 'Nama Tetap Valid',
        ]);
    }

    public function test_nim_owned_by_another_alumni_is_rejected_by_validation(): void
    {
        $alumni = $this->createAlumni('22000003');
        $this->createAlumni('22000004');

        $response = $this
            ->from(route('admin.alumni.edit', $alumni->id))
            ->put(route('admin.alumni.update', $alumni->id), [
                'nim' => '22000004',
                'nama_lengkap' => $alumni->nama_lengkap,
                'jenis_kelamin' => $alumni->jenis_kelamin,
            ]);

        $response
            ->assertRedirect(route('admin.alumni.edit', $alumni->id))
            ->assertSessionHasErrors('nim');

        $this->assertDatabaseHas('alumnis', [
            'id' => $alumni->id,
            'nim' => '22000003',
        ]);
    }

    public function test_missing_jenis_kelamin_is_rejected_by_validation(): void
    {
        $alumni = $this->createAlumni('22000005');

        $response = $this
            ->from(route('admin.alumni.edit', $alumni->id))
            ->put(route('admin.alumni.update', $alumni->id), [
                'nim' => $alumni->nim,
                'nama_lengkap' => $alumni->nama_lengkap,
            ]);

        $response
            ->assertRedirect(route('admin.alumni.edit', $alumni->id))
            ->assertSessionHasErrors('jenis_kelamin');
    }

    private function createAlumni(string $nim): Alumni
    {
        $alumni = Alumni::create([
            'nim' => $nim,
            'nama_lengkap' => 'Alumni '.$nim,
            'jenis_kelamin' => 'L',
            'email' => $nim.'@example.test',
            'no_hp' => '08123456789',
        ]);

        AlumniAkademik::create([
            'alumni_id' => $alumni->id,
            'angkatan' => 2022,
            'tahun_lulus' => 2026,
            'tahun_yudisium' => 2026,
            'judul_skripsi' => 'Judul lama',
            'ipk' => 3.75,
            'nilai_toefl' => 525,
            'lama_studi' => 48,
        ]);

        AlamatAlumni::create([
            'alumni_id' => $alumni->id,
            'alamat_lengkap' => 'Alamat lama',
            'kota' => 'Banjarmasin',
            'provinsi' => 'Kalimantan Selatan',
            'latitude' => -3.316694,
            'longitude' => 114.590111,
            'is_current' => true,
        ]);

        return $alumni;
    }
}
