<?php

namespace App\Services;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Support\Facades\Http;

/**
 * Memvalidasi pilihan wilayah dan menurunkan (derive) nama resmi dari master data.
 *
 * Aturan per level wilayah Indonesia:
 *  - ID dikirim dan cocok dengan master data  -> terverifikasi, nama diambil dari master.
 *  - ID dikirim tetapi tidak cocok            -> error (mencegah kombinasi ilegal).
 *  - ID tidak dikirim, label dikirim          -> label disimpan sebagai snapshot tanpa ID
 *                                                (kompatibel dengan client lama, tanpa data loss).
 *
 * Untuk luar negeri, ID membawa adminCode1/geonameId GeoNames dan label dikirim
 * bersamaannya sebagai snapshot teks (GeoNames tidak dimodelkan di database lokal).
 */
class RegionResolver
{
    /**
     * @return array{
     *     negara: string|null, negara_kode: string|null,
     *     provinsi: string|null, provinsi_id: string|null,
     *     kota: string|null, kota_id: string|null,
     *     kecamatan: string|null, kecamatan_id: string|null,
     *     errors: array<string, string>
     * }
     */
    public function resolve(
        ?string $negaraKode,
        ?string $provinsiId,
        ?string $kotaId,
        ?string $kecamatanId,
        ?string $negaraLabel = null,
        ?string $provinsiLabel = null,
        ?string $kotaLabel = null,
        ?string $kecamatanLabel = null,
    ): array {
        // --- BENTROK MODE: Indonesia ↔ luar negeri ---
        // Kecamatan hanya ada di mode Indonesia; begitu negara bukan ID,
        // sisa kecamatan dari mode lama wajib dibuang agar tidak tersimpan.
        if (filled($negaraKode) && strtoupper(trim($negaraKode)) !== 'ID') {
            $kecamatanId = null;
            $kecamatanLabel = null;
        }

        $result = [
            'negara' => null,
            'negara_kode' => null,
            'provinsi' => null,
            'provinsi_id' => null,
            'kota' => null,
            'kota_id' => null,
            'kecamatan' => null,
            'kecamatan_id' => null,
            'errors' => [],
        ];

        $negaraLabel = filled($negaraLabel) ? trim($negaraLabel) : null;

        if (blank($negaraKode)) {
            if (blank($negaraLabel)) {
                $result['errors']['negara'] = 'Negara wajib dipilih.';

                return $result;
            }

            // Mode legacy: hanya label yang tersedia, tanpa verifikasi.
            $result['negara'] = $negaraLabel;
            $result['provinsi'] = filled($provinsiLabel) ? trim($provinsiLabel) : null;
            $result['kota'] = filled($kotaLabel) ? trim($kotaLabel) : null;
            $result['kecamatan'] = filled($kecamatanLabel) ? trim($kecamatanLabel) : null;

            return $result;
        }

        $negaraKode = strtoupper(trim($negaraKode));

        if (! preg_match('/^[A-Z]{2}$/', $negaraKode)) {
            $result['errors']['negara'] = 'Kode negara harus berupa ISO alpha-2.';

            return $result;
        }

        $result['negara_kode'] = $negaraKode;

        if ($negaraKode === 'ID') {
            $result['negara'] = 'Indonesia';

            return $this->resolveIndonesia($result, $provinsiId, $kotaId, $kecamatanId, $provinsiLabel, $kotaLabel, $kecamatanLabel);
        }

        $result['negara'] = $negaraLabel;

        return $this->resolveInternational($result, $provinsiId, $kotaId, $provinsiLabel, $kotaLabel);
    }

    /**
     * Resolve khusus pembaruan data (profile/admin update).
     *
     * Sama seperti resolve(), tetapi fallback ke data tersimpan dilakukan
     * per-mode: bila form memindahkan user ke mode lain (Indonesia ↔ luar
     * negeri), ID/label sisi lama TIDAK diikutkan sebagai fallback — hanya
     * negara dari form yang berlaku. Mencegah data campuran seperti
     * "Germany + kecamatan Coblong" atau "Indonesia + adminCode1 milik
     * provinsi BPS".
     *
     * Aturan mode: negara_kode form yang valid ('ID' atau ISO alpha-2) menang;
     * bila tidak dikirim, mode diambil dari negara_kode tersimpan.
     */
    public function resolveForUpdate(
        array $form,
        array $stored,
    ): array {
        $formKode = filled($form['negara_kode'] ?? null) ? strtoupper(trim((string) $form['negara_kode'])) : null;
        $storedKode = filled($stored['negara_kode'] ?? null) ? strtoupper(trim((string) $stored['negara_kode'])) : null;

        $formValid = $formKode !== null && preg_match('/^[A-Z]{2}$/', $formKode) === 1;
        $storedValid = $storedKode !== null && preg_match('/^[A-Z]{2}$/', $storedKode) === 1;

        $modeBerubah = false;

        if ($formValid && $storedValid) {
            $formKeIndo = $formKode === 'ID';
            $storedKeIndo = $storedKode === 'ID';
            $modeBerubah = $formKeIndo !== $storedKeIndo;
        }

        if (! $modeBerubah) {
            // Mode sama (atau tidak bisa ditentukan): perilaku lama — fallback penuh.
            return $this->resolve(
                $formKode ?? $storedKode,
                $form['provinsi_id'] ?? ($stored['provinsi_id'] ?? null),
                $form['kota_id'] ?? ($stored['kota_id'] ?? null),
                $form['kecamatan_id'] ?? ($stored['kecamatan_id'] ?? null),
                $form['negara'] ?? ($stored['negara'] ?? null),
                $form['provinsi'] ?? ($stored['provinsi'] ?? null),
                $form['kota'] ?? ($stored['kota'] ?? null),
                $form['kecamatan'] ?? ($stored['kecamatan'] ?? null),
            );
        }

        // Mode berubah: sisi lama dibuang seluruhnya, sisi baru yang menang.
        if ($formKode === 'ID') {
            return $this->resolve(
                'ID',
                $form['provinsi_id'] ?? null,
                $form['kota_id'] ?? null,
                $form['kecamatan_id'] ?? null,
                'Indonesia',
                $form['provinsi'] ?? null,
                $form['kota'] ?? null,
                $form['kecamatan'] ?? null,
            );
        }

        return $this->resolve(
            $formKode,
            $form['provinsi_id'] ?? null,
            $form['kota_id'] ?? null,
            null,
            $form['negara'] ?? null,
            $form['provinsi'] ?? null,
            $form['kota'] ?? null,
            null,
        );
    }

    private function resolveIndonesia(
        array $result,
        ?string $provinsiId,
        ?string $kotaId,
        ?string $kecamatanId,
        ?string $provinsiLabel,
        ?string $kotaLabel,
        ?string $kecamatanLabel,
    ): array {
        $provinsiLabel = filled($provinsiLabel) ? trim($provinsiLabel) : null;
        $kotaLabel = filled($kotaLabel) ? trim($kotaLabel) : null;
        $kecamatanLabel = filled($kecamatanLabel) ? trim($kecamatanLabel) : null;

        $provinsi = filled($provinsiId) ? Province::query()->find($provinsiId) : null;

        if ($provinsi) {
            $result['provinsi'] = $provinsi->name;
            // (string) untuk konsistensi lintas-driver: SQLite bisa mengembalikan
            // ID char sebagai integer, MySQL sebagai string.
            $result['provinsi_id'] = (string) $provinsi->id;
        } elseif ($provinsiLabel !== null) {
            // ID tidak diberikan (client lama): simpan label sebagai snapshot.
            $result['provinsi'] = $provinsiLabel;
        } elseif (filled($provinsiId)) {
            $result['errors']['provinsi'] = 'Provinsi tidak ditemukan.';

            return $result;
        }

        // Rantai child hanya bisa diverifikasi bila parent terverifikasi by-ID.
        if (! $provinsi) {
            $result['kota'] = $kotaLabel;
            $result['kecamatan'] = $kecamatanLabel;

            return $result;
        }

        $kota = filled($kotaId) ? Regency::query()->where('province_id', $provinsi->id)->find($kotaId) : null;

        if ($kota) {
            $result['kota'] = $kota->name;
            $result['kota_id'] = (string) $kota->id;
        } elseif (filled($kotaId)) {
            $result['errors']['kota'] = 'Kota/kabupaten tidak ditemukan pada provinsi yang dipilih.';

            return $result;
        } elseif ($kotaLabel !== null) {
            $result['kota'] = $kotaLabel;
        }

        if (! $kota) {
            $result['kecamatan'] = $kecamatanLabel;

            return $result;
        }

        $kecamatan = filled($kecamatanId) ? District::query()->where('regency_id', $kota->id)->find($kecamatanId) : null;

        if ($kecamatan) {
            $result['kecamatan'] = $kecamatan->name;
            $result['kecamatan_id'] = (string) $kecamatan->id;
        } elseif (filled($kecamatanId)) {
            $result['errors']['kecamatan'] = 'Kecamatan tidak ditemukan pada kota yang dipilih.';

            return $result;
        } elseif ($kecamatanLabel !== null) {
            $result['kecamatan'] = $kecamatanLabel;
        }

        return $result;
    }

    private function resolveInternational(
        array $result,
        ?string $provinsiId,
        ?string $kotaId,
        ?string $provinsiLabel,
        ?string $kotaLabel,
    ): array {
        // Di luar Indonesia, "provinsi_id" membawa adminCode1 dan "kota_id"
        // membawa geonameId GeoNames. Kecamatan tidak dimodelkan di sana.
        $result['provinsi_id'] = filled($provinsiId) ? mb_substr(trim($provinsiId), 0, 20) : null;
        $result['kota_id'] = filled($kotaId) ? mb_substr(trim($kotaId), 0, 20) : null;
        $result['provinsi'] = filled($provinsiLabel) ? trim($provinsiLabel) : null;
        $result['kota'] = filled($kotaLabel) ? trim($kotaLabel) : null;
        $result['kecamatan'] = null;
        $result['kecamatan_id'] = null;

        return $result;
    }

    /**
     * Lengkapi snapshot label luar negeri yang belum dikirim klien berdasarkan
     * kode GeoNames. Gagal jaringan bersifat non-fatal: snapshot boleh kosong.
     *
     * @param  array{negara: string|null, negara_kode: string|null, provinsi: string|null, provinsi_id: string|null, kota: string|null, kota_id: string|null}  $region
     * @return array{negara: string|null, provinsi: string|null, kota: string|null}
     */
    public function fillForeignLabels(array $region): array
    {
        $username = config('services.geonames.username');

        $labels = [
            'negara' => $region['negara'],
            'provinsi' => $region['provinsi'],
            'kota' => $region['kota'],
        ];

        if (blank($username) || blank($region['negara_kode']) || $region['negara_kode'] === 'ID') {
            return $labels;
        }

        try {
            if (blank($labels['negara'])) {
                $country = Http::acceptJson()
                    ->timeout(5)
                    ->get('https://secure.geonames.org/countryInfoJSON', [
                        'country' => $region['negara_kode'],
                        'username' => $username,
                    ])
                    ->json('geonames.0.countryName');
                $labels['negara'] = filled($country) ? trim((string) $country) : null;
            }

            if (blank($labels['provinsi']) && filled($region['provinsi_id'])) {
                $subdivision = Http::acceptJson()
                    ->timeout(5)
                    ->get('https://secure.geonames.org/searchJSON', [
                        'country' => $region['negara_kode'],
                        'featureCode' => 'ADM1',
                        'adminCode1' => $region['provinsi_id'],
                        'maxRows' => 1,
                        'username' => $username,
                    ])
                    ->json('geonames.0.name');
                $labels['provinsi'] = filled($subdivision) ? trim((string) $subdivision) : null;
            }

            if (blank($labels['kota']) && filled($region['kota_id'])) {
                $city = Http::acceptJson()
                    ->timeout(5)
                    ->get('https://secure.geonames.org/getJSON', [
                        'geonameId' => $region['kota_id'],
                        'username' => $username,
                    ])
                    ->json('name');
                $labels['kota'] = filled($city) ? trim((string) $city) : null;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $labels;
    }
}
