<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProvincesTables extends Migration
{
    public function up()
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->char('id', 2)->index();
            $table->string('name');
        });
    }

    public function down()
    {
        Schema::drop('provinces');
    }
}
