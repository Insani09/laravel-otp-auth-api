<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbesar kolom `code` supaya bisa menyimpan hash OTP (sha256 = 64 karakter hex),
     * bukan lagi kode OTP dalam bentuk plaintext 6 digit.
     */
    public function up(): void
    {
        Schema::table('login_otps', function (Blueprint $table) {
            $table->string('code', 64)->change();
        });
    }

    public function down(): void
    {
        Schema::table('login_otps', function (Blueprint $table) {
            $table->string('code', 6)->change();
        });
    }
};