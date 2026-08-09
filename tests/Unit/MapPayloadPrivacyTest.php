<?php

namespace Tests\Unit;

use App\Http\Controllers\MapController;
use ReflectionMethod;
use Tests\TestCase;

class MapPayloadPrivacyTest extends TestCase
{
    public function test_sensitive_map_details_are_removed_for_non_admin_payloads(): void
    {
        $controller = new MapController();
        $method = new ReflectionMethod($controller, 'withoutSensitiveAlumniDetails');

        $payload = $method->invoke($controller, [
            'markers' => [[
                'nama' => 'Alumni Test',
                'nim' => '2210810001',
                'alamat' => 'Jl. Contoh No. 1',
                'linearitas' => 'Erat',
                'judul_skripsi' => 'Skripsi Rahasia',
                'link_linkedin' => 'linkedin.com/in/alumni-test',
                'angkatan' => 2022,
            ]],
            'studi_lanjut_markers' => [[
                'nama_lengkap' => 'Alumni Test',
                'nim' => '2210810001',
                'kampus' => 'Universitas Contoh',
            ]],
        ]);

        $this->assertSame([
            'nama' => 'Alumni Test',
            'angkatan' => 2022,
        ], $payload['markers'][0]);
        $this->assertSame([
            'nama_lengkap' => 'Alumni Test',
            'kampus' => 'Universitas Contoh',
        ], $payload['studi_lanjut_markers'][0]);
    }
}
