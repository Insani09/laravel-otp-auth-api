<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skema countries/cities adalah sisa eksperimen yang tak pernah dipakai
        // (lihat ANALISIS_SISTEM_WILAYAH.md): modelnya mati dan tidak ada data.
        // Dihapus agar tidak membingungkan; mirror skema lama disimpan di down().
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }

    public function down(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('admin_code', 20); // Mengacu ke provinsi
            $table->string('name');
            $table->timestamps();
        });
    }
};
