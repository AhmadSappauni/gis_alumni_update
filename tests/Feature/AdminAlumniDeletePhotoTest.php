<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAlumniDeletePhotoTest extends TestCase
{
    private array $originalSupabaseEnv = [];

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

        $admin = new User([
            'name' => 'Admin Test',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'password' => 'password',
        ]);
        $admin->id = 1;
        $admin->exists = true;

        $this->actingAs($admin);
        Storage::fake('public');

        foreach (['SUPABASE_URL', 'SUPABASE_KEY', 'SUPABASE_BUCKET'] as $key) {
            $this->originalSupabaseEnv[$key] = Env::get($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->originalSupabaseEnv as $key => $value) {
            if ($value === null) {
                Env::getRepository()->clear($key);
            } else {
                Env::getRepository()->set($key, (string) $value);
            }
        }

        parent::tearDown();
    }

    public function test_single_delete_removes_local_profile_photo(): void
    {
        $path = 'alumni_foto/local-photo.jpg';
        Storage::disk('public')->put($path, 'photo-content');
        $alumni = $this->createAlumni('22000001', $path);

        $response = $this
            ->from(route('admin.alumni.index'))
            ->delete(route('admin.alumni.destroy', $alumni->id));

        $response->assertRedirect(route('admin.alumni.index'));
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('alumnis', ['id' => $alumni->id]);
    }

    public function test_single_delete_without_photo_succeeds(): void
    {
        $alumni = $this->createAlumni('22000002');

        $response = $this
            ->from(route('admin.alumni.index'))
            ->delete(route('admin.alumni.destroy', $alumni->id));

        $response->assertRedirect(route('admin.alumni.index'));
        $this->assertDatabaseMissing('alumnis', ['id' => $alumni->id]);
    }

    public function test_bulk_delete_removes_local_photo_and_accepts_null_photo(): void
    {
        $path = 'alumni_foto/bulk-photo.jpg';
        Storage::disk('public')->put($path, 'photo-content');
        $withPhoto = $this->createAlumni('22000003', $path);
        $withoutPhoto = $this->createAlumni('22000004');

        $response = $this->deleteJson(route('admin.alumni.bulk-destroy'), [
            'ids' => [$withPhoto->id, $withoutPhoto->id],
            'batch_size' => 10,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted' => 2,
            ]);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('alumnis', ['id' => $withPhoto->id]);
        $this->assertDatabaseMissing('alumnis', ['id' => $withoutPhoto->id]);
    }

    public function test_first_select_all_batch_returns_current_total_only_when_requested(): void
    {
        foreach (range(1, 25) as $index) {
            $this->createAlumni('23' . str_pad((string) $index, 6, '0', STR_PAD_LEFT));
        }

        $firstBatch = $this->deleteJson(route('admin.alumni.bulk-destroy'), [
            'select_all' => true,
            'include_total' => true,
            'batch_size' => 10,
        ]);

        $firstBatch
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted' => 10,
                'total' => 25,
            ]);
        $this->assertDatabaseCount('alumnis', 15);

        $secondBatch = $this->deleteJson(route('admin.alumni.bulk-destroy'), [
            'select_all' => true,
            'batch_size' => 10,
        ]);

        $secondBatch
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted' => 10,
            ])
            ->assertJsonMissingPath('total');
        $this->assertDatabaseCount('alumnis', 5);
    }

    public function test_single_delete_removes_supabase_profile_photo(): void
    {
        $this->setSupabaseEnv();
        $publicUrl = 'https://project.supabase.co/storage/v1/object/public/alumni/profile.jpg';
        $deleteUrl = 'https://project.supabase.co/storage/v1/object/alumni/profile.jpg';
        Http::fake([$deleteUrl => Http::response('', 200)]);
        $alumni = $this->createAlumni('22000005', $publicUrl);

        $this
            ->from(route('admin.alumni.index'))
            ->delete(route('admin.alumni.destroy', $alumni->id))
            ->assertRedirect(route('admin.alumni.index'));

        Http::assertSent(function ($request) use ($deleteUrl) {
            return $request->method() === 'DELETE'
                && $request->url() === $deleteUrl
                && $request->hasHeader('apikey', 'test-key')
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
        $this->assertDatabaseMissing('alumnis', ['id' => $alumni->id]);
    }

    public function test_supabase_delete_failure_is_logged_without_blocking_alumni_delete(): void
    {
        $this->setSupabaseEnv();
        $publicUrl = 'https://project.supabase.co/storage/v1/object/public/alumni/missing.jpg';
        $deleteUrl = 'https://project.supabase.co/storage/v1/object/alumni/missing.jpg';
        Http::fake([$deleteUrl => Http::response('', 404)]);
        Log::spy();
        $alumni = $this->createAlumni('22000006', $publicUrl);

        $this
            ->from(route('admin.alumni.index'))
            ->delete(route('admin.alumni.destroy', $alumni->id))
            ->assertRedirect(route('admin.alumni.index'));

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) use ($alumni) {
                return $message === 'Foto profil Supabase gagal dihapus.'
                    && ($context['alumni_id'] ?? null) === $alumni->id
                    && ($context['status'] ?? null) === 404;
            });
        $this->assertDatabaseMissing('alumnis', ['id' => $alumni->id]);
    }

    private function createAlumni(string $nim, ?string $photo = null): Alumni
    {
        return Alumni::create([
            'nim' => $nim,
            'nama_lengkap' => 'Alumni ' . $nim,
            'jenis_kelamin' => 'L',
            'foto_profil' => $photo,
        ]);
    }

    private function setSupabaseEnv(): void
    {
        Env::getRepository()->set('SUPABASE_URL', 'https://project.supabase.co');
        Env::getRepository()->set('SUPABASE_KEY', 'test-key');
        Env::getRepository()->set('SUPABASE_BUCKET', 'alumni');
    }
}
