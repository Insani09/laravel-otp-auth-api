<?php

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'negara_kode')) {
                $table->string('negara_kode', 2)->nullable()->after('negara');
            }
            if (! Schema::hasColumn('users', 'provinsi_id')) {
                $table->string('provinsi_id', 20)->nullable()->after('provinsi');
            }
            if (! Schema::hasColumn('users', 'kota_id')) {
                $table->string('kota_id', 20)->nullable()->after('kota');
            }
            if (! Schema::hasColumn('users', 'kecamatan_id')) {
                $table->string('kecamatan_id', 20)->nullable()->after('kecamatan');
            }
        });

        // Backfill ID wilayah untuk data lama yang hanya menyimpan label teks.
        // Data tak cocok dibiarkan NULL (snapshot teks tetap dipertahankan).
        $this->backfillIndonesiaIds();
    }

    private function backfillIndonesiaIds(): void
    {
        $users = DB::table('users')
            ->whereNotNull('negara')
            ->whereNull('negara_kode')
            ->get(['id', 'negara', 'provinsi', 'kota', 'kecamatan']);

        foreach ($users as $user) {
            $negara = trim((string) $user->negara);

            if (mb_strtolower($negara) !== 'indonesia') {
                continue;
            }

            $provinsi = filled($user->provinsi)
                ? Province::query()->where('name', $user->provinsi)->first()
                : null;

            $kota = filled($user->kota) && $provinsi
                ? Regency::query()->where('province_id', $provinsi->id)->where('name', $user->kota)->first()
                : null;

            $kecamatan = filled($user->kecamatan) && $kota
                ? District::query()->where('regency_id', $kota->id)->where('name', $user->kecamatan)->first()
                : null;

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'negara_kode' => 'ID',
                    'provinsi_id' => $provinsi?->id,
                    'kota_id' => $kota?->id,
                    'kecamatan_id' => $kecamatan?->id,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            foreach (['negara_kode', 'provinsi_id', 'kota_id', 'kecamatan_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
