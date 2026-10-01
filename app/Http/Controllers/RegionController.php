<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RegionController extends Controller
{
    /**
     * Data administrasi Indonesia selalu berasal dari database IndoRegion.
     */
    public function getProvinces()
    {
        return response()->json(
            Province::query()
                ->select(['id', 'name'])
                ->distinct()
                ->orderBy('name')
                ->get()
        );
    }

    public function getRegencies($provinceId)
    {
        return response()->json(
            Regency::query()
                ->where('province_id', $provinceId)
                ->select(['id', 'name'])
                ->distinct()
                ->orderBy('name')
                ->get()
        );
    }

    public function getDistricts($regencyId)
    {
        return response()->json(
            District::query()
                ->where('regency_id', $regencyId)
                ->select(['id', 'name'])
                ->distinct()
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Global country list. Jangan panggil GeoNames langsung dari browser;
     * kredensial tetap berada di server Laravel.
     */
    public function getCountries(): JsonResponse
    {
        $payload = $this->geoNames('countryInfoJSON');
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $countries = collect($payload['geonames'] ?? [])
            ->map(fn (array $country) => [
                'id' => strtoupper((string) ($country['countryCode'] ?? '')),
                'text' => trim((string) ($country['countryName'] ?? '')),
            ])
            ->filter(fn (array $country) => preg_match('/^[A-Z]{2}$/', $country['id']) && $country['text'] !== '')
            ->unique('id')
            ->sortBy(fn (array $country) => $country['id'] === 'ID' ? '0-'.$country['text'] : '1-'.$country['text'], SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['results' => $countries]);
    }

    /**
     * Provinsi/state untuk negara selain Indonesia.
     */
    public function getSubdivisions(string $countryCode): JsonResponse
    {
        $countryCode = strtoupper($countryCode);
        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return response()->json(['message' => 'Kode negara harus berupa ISO alpha-2.'], 422);
        }

        $payload = $this->geoNames('searchJSON', [
            'country' => $countryCode,
            'featureCode' => 'ADM1',
            'maxRows' => 300,
        ]);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $subdivisions = collect($payload['geonames'] ?? [])
            ->map(fn (array $item) => [
                'id' => (string) ($item['adminCode1'] ?? ''),
                'text' => trim((string) ($item['name'] ?? '')),
            ])
            ->filter(fn (array $item) => $item['id'] !== '' && $item['text'] !== '')
            ->unique('id')
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['results' => $subdivisions]);
    }

    /**
     * Kota untuk negara selain Indonesia. geonameId dipakai sebagai ID eksternal,
     * bukan nama kota, sehingga format data tetap eksplisit.
     */
    public function getCities(string $countryCode, string $adminCode1): JsonResponse
    {
        $countryCode = strtoupper($countryCode);
        if (! preg_match('/^[A-Z]{2}$/', $countryCode) || $adminCode1 === '') {
            return response()->json(['message' => 'Parameter negara atau subdivisi tidak valid.'], 422);
        }

        $payload = $this->geoNames('searchJSON', [
            'country' => $countryCode,
            'adminCode1' => $adminCode1,
            'featureClass' => 'P',
            'maxRows' => 300,
        ]);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $cities = collect($payload['geonames'] ?? [])
            ->map(fn (array $item) => [
                'id' => (string) ($item['geonameId'] ?? ''),
                'text' => trim((string) ($item['name'] ?? '')),
            ])
            ->filter(fn (array $item) => $item['id'] !== '' && $item['text'] !== '')
            ->unique('id')
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json(['results' => $cities]);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function geoNames(string $endpoint, array $query = []): array|JsonResponse
    {
        $username = config('services.geonames.username');
        if (blank($username)) {
            return response()->json([
                'message' => 'Layanan wilayah global belum dikonfigurasi.',
            ], 503);
        }

        // Cache hasil GeoNames per endpoint+parameter agar pengguna berikutnya
        // tidak memicu request eksternal berulang (hemat kuota, respons lebih cepat).
        $cacheKey = 'geonames:'.$endpoint.':'.md5(json_encode($query));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) { 
            return $cached;
        }

        // Saat cache kedaluwarsa tapi snapshot stale masih ada, sajikan stale
        // alih-alih menampilkan error ke pengguna (graceful degradation).
        $staleKey = $cacheKey.':stale';

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->retry(2, 200)
                ->get("https://secure.geonames.org/{$endpoint}", array_merge($query, [
                    'username' => $username,
                ]));
        } catch (ConnectionException $exception) {
            report($exception);

            $stale = Cache::get($staleKey);
            if ($stale !== null) {
                return $stale;
            }

            return response()->json([
                'message' => 'Layanan wilayah global tidak dapat dihubungi.',
            ], 503);
        }

        $payload = $response->json();
        if (! $response->successful() || data_get($payload, 'status.message')) {
            $stale = Cache::get($staleKey);
            if ($stale !== null) {
                return $stale;
            }

            return response()->json([
                'message' => 'Layanan wilayah global gagal merespons.',
            ], 502);
        }

        $data = is_array($payload) ? $payload : [];

        Cache::put($cacheKey, $data, now()->addHours(12));
        // Snapshot stale disimpan lebih lama untuk fallback saat GeoNames down.
        Cache::put($staleKey, $data, now()->addDays(7));

        return $data;
    }
}
