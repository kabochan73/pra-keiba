<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->string('race_id', 20)->unique()->comment('netkeibaのレースID (例: 202501010101)');
            $table->string('name')->comment('レース名');
            $table->date('race_date')->comment('開催日');
            $table->string('venue')->comment('競馬場');
            $table->integer('race_number')->comment('レース番号');
            $table->string('course')->nullable()->comment('コース (芝/ダート)');
            $table->integer('distance')->nullable()->comment('距離(m)');
            $table->string('direction')->nullable()->comment('回り (右/左)');
            $table->string('weather')->nullable()->comment('天気');
            $table->string('track_condition')->nullable()->comment('馬場状態');
            $table->string('grade')->nullable()->comment('グレード (G1/G2/G3など)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
