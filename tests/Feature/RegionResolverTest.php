<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RegionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegionResolverTest extends TestCase
{
    use RefreshDatabase;

    private RegionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new RegionResolver;

        $this->seedMasterData();
    }

    /**
     * Seed mini master data IndoRegion (provinces/regencies/districts memakai
     * char ID non-increment, jadi diisi lewat DB statement langsung).
     */
    private function seedMasterData(): void
    {
        DB::table('provinces')->insert(['id' => '32', 'name' => 'Jawa Barat']);
        DB::table('regencies')->insert(['id' => '3273', 'province_id' => '32', 'name' => 'Kota Bandung']);
        DB::table('districts')->insert(['id' => '3273010', 'regency_id' => '3273', 'name' => 'Coblong']);
    }

    public function test_it_resolves_valid_indonesia_chain_from_ids(): void
    {
        $region = $this->resolver->resolve('ID', '32', '3273', '3273010');

        $this->assertSame([], $region['errors']);
        $this->assertSame('Indonesia', $region['negara']);
        $this->assertSame('Jawa Barat', $region['provinsi']);
        $this->assertSame('Kota Bandung', $region['kota']);
        $this->assertSame('Coblong', $region['kecamatan']);
        $this->assertSame('32', $region['provinsi_id']);
        $this->assertSame('3273', $region['kota_id']);
        $this->assertSame('3273010', $region['kecamatan_id']);
    }

    public function test_it_rejects_child_from_different_parent(): void
    {
        // Regency valid, tapi bukan anak dari provinsi 32.
        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI Jakarta']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'Jakarta Selatan']);

        $region = $this->resolver->resolve('ID', '32', '3171', null);

        $this->assertArrayHasKey('kota', $region['errors']);
    }

    public function test_it_rejects_unknown_province_id(): void
    {
        $region = $this->resolver->resolve('ID', '99', null, null);

        $this->assertArrayHasKey('provinsi', $region['errors']);
    }

    public function test_it_keeps_legacy_labels_without_ids(): void
    {
        // Client lama hanya mengirim label: snapshot disimpan tanpa ID, tanpa error.
        $region = $this->resolver->resolve(null, null, null, null, 'Indonesia', 'Jawa Barat', 'Kota Bandung', 'Coblong');

        $this->assertSame([], $region['errors']);
        $this->assertSame('Indonesia', $region['negara']);
        $this->assertSame('Jawa Barat', $region['provinsi']);
        $this->assertNull($region['provinsi_id']);
    }

    public function test_it_requires_country(): void
    {
        $region = $this->resolver->resolve(null, null, null, null);

        $this->assertArrayHasKey('negara', $region['errors']);
    }

    public function test_it_rejects_non_iso_country_code(): void
    {
        $region = $this->resolver->resolve('INVALID', null, null, null);

        $this->assertArrayHasKey('negara', $region['errors']);
    }

    public function test_it_stores_foreign_codes_as_snapshot(): void
    {
        $region = $this->resolver->resolve('US', 'CA', '5391959', null, 'Amerika Serikat', 'California', 'San Francisco');

        $this->assertSame([], $region['errors']);
        $this->assertSame('US', $region['negara_kode']);
        $this->assertSame('CA', $region['provinsi_id']);
        $this->assertSame('5391959', $region['kota_id']);
        $this->assertSame('California', $region['provinsi']);
        $this->assertNull($region['kecamatan_id']);
    }

    /**
     * Skenario owner: user Indonesia (punya kecamatan) pindah ke luar negeri.
     * Sisi lama (ID provinsi/kota/kecamatan BPS) tidak boleh terselundup —
     * sekalipun form luar negeri hanya mengirim negara (tanpa ID daerah).
     */
    public function test_switching_from_indonesia_to_foreign_discards_old_side(): void
    {
        $stored = [
            'negara' => 'Indonesia', 'negara_kode' => 'ID',
            'provinsi' => 'JAWA BARAT', 'provinsi_id' => '32',
            'kota' => 'KOTA BANDUNG', 'kota_id' => '3273',
            'kecamatan' => 'COBLONG', 'kecamatan_id' => '3273010',
        ];

        // Form luar negeri sederhana: hanya kode negara (daerah belum dipilih).
        $region = $this->resolver->resolveForUpdate(
            ['negara' => 'Germany', 'negara_kode' => 'DE'],
            $stored,
        );

        $this->assertSame([], $region['errors']);
        $this->assertSame('DE', $region['negara_kode']);
        $this->assertSame('Germany', $region['negara']);
        // Sisi Indonesia lama dibuang seluruhnya — tidak ada data campuran.
        $this->assertNull($region['provinsi_id']);
        $this->assertNull($region['provinsi']);
        $this->assertNull($region['kota_id']);
        $this->assertNull($region['kota']);
        $this->assertNull($region['kecamatan_id']);
        $this->assertNull($region['kecamatan']);
    }

    /** Kebalikannya: user luar negeri pindah ke Indonesia — sama bersih. */
    public function test_switching_from_foreign_to_indonesia_discards_old_side(): void
    {
        $stored = [
            'negara' => 'Germany', 'negara_kode' => 'DE',
            'provinsi' => 'Hamburg', 'provinsi_id' => 'HH',
            'kota' => 'Hamburg', 'kota_id' => '2911298',
            'kecamatan' => null, 'kecamatan_id' => null,
        ];

        // (master data sudah di-seed oleh setUp())

        $region = $this->resolver->resolveForUpdate(
            ['negara' => 'Indonesia', 'negara_kode' => 'ID', 'provinsi_id' => '32', 'kota_id' => '3273'],
            $stored,
        );

        $this->assertSame([], $region['errors']);
        $this->assertSame('ID', $region['negara_kode']);
        $this->assertSame('Indonesia', $region['negara']);
        $this->assertSame('32', $region['provinsi_id']);
        $this->assertSame('3273', $region['kota_id']);
        // Sisi luar negeri lama dibuang.
        $this->assertNull($region['kecamatan_id']);
    }

    /** Edit parsial (ubah nama saja) TETAP mempertahankan wilayah tersimpan. */
    public function test_partial_edit_keeps_stored_region(): void
    {
        $stored = [
            'negara' => 'Indonesia', 'negara_kode' => 'ID',
            'provinsi' => 'JAWA BARAT', 'provinsi_id' => '32',
            'kota' => 'KOTA BANDUNG', 'kota_id' => '3273',
            'kecamatan' => 'COBLONG', 'kecamatan_id' => '3273010',
        ];

        $region = $this->resolver->resolveForUpdate(
            [], // form tidak mengirim wilayah sama sekali
            $stored,
        );

        $this->assertSame([], $region['errors']);
        $this->assertSame('32', $region['provinsi_id']);
        $this->assertSame('3273', $region['kota_id']);
        $this->assertSame('3273010', $region['kecamatan_id']);
    }
}
