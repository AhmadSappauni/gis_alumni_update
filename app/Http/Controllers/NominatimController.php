<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class NominatimController extends Controller
{
    public function reverse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lon' => ['required', 'numeric', 'between:-180,180'],
            'zoom' => ['nullable', 'integer', 'between:0,18'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Input tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lat = (float) $validator->validated()['lat'];
        $lon = (float) $validator->validated()['lon'];
        $zoom = (int) ($validator->validated()['zoom'] ?? 18);

        $baseUrl = config('services.nominatim.base_url', 'https://nominatim.openstreetmap.org');
        $email = (string) config('services.nominatim.email', env('NOMINATIM_EMAIL', ''));
        $userAgent = (string) config(
            'services.nominatim.user_agent',
            env('NOMINATIM_USER_AGENT', 'gis_alumni_4/1.0 (Laravel; Nominatim proxy)')
        );

        $query = [
            'format' => 'jsonv2',
            'addressdetails' => 1,
            'lat' => $lat,
            'lon' => $lon,
            'zoom' => $zoom,
        ];

        if ($email !== '') {
            $query['email'] = $email;
        }

        $res = Http::withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => $userAgent,
        ])
            ->timeout(10)
            ->get(rtrim($baseUrl, '/') . '/reverse', $query);

        if (!$res->ok()) {
            return response()->json([
                'message' => 'Nominatim request gagal.',
                'status' => $res->status(),
                'body' => $res->json() ?? $res->body(),
            ], 502);
        }

        return response()->json($res->json());
    }
}

