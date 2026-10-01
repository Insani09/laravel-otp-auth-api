<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'otp_code')) {
                $table->string('otp_code', 10)->nullable()->after('kecamatan');
            }
            if (! Schema::hasColumn('users', 'otp_expires_at')) {
                $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            }
        });

        // ->change() native Laravel mendukung MySQL, Postgres, dan SQLite.
        // Sebelumnya memakai "ALTER TABLE ... MODIFY" yang hanya valid di MySQL.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'otp_code')) {
                $columns[] = 'otp_code';
            }
            if (Schema::hasColumn('users', 'otp_expires_at')) {
                $columns[] = 'otp_expires_at';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->change();
        });
    }
};
