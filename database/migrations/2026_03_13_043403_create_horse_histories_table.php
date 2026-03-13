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
        Schema::create('horse_histories', function (Blueprint $table) {
            $table->id();
            $table->string('horse_name')->comment('馬名');
            $table->string('horse_id')->comment('netkeibaの馬ID');
            $table->date('race_date')->comment('レース日');
            $table->string('venue')->nullable()->comment('開催（例: 2阪神6）');
            $table->string('weather')->nullable()->comment('天気');
            $table->integer('race_number')->nullable()->comment('R番号');
            $table->string('race_name')->nullable()->comment('レース名');
            $table->integer('horses_count')->nullable()->comment('頭数');
            $table->integer('gate_number')->nullable()->comment('枠番');
            $table->integer('horse_number')->nullable()->comment('馬番');
            $table->decimal('win_odds', 8, 1)->nullable()->comment('オッズ');
            $table->integer('popularity')->nullable()->comment('人気');
            $table->string('rank')->nullable()->comment('着順');
            $table->string('jockey')->nullable()->comment('騎手');
            $table->decimal('burden_weight', 4, 1)->nullable()->comment('斤量');
            $table->string('course')->nullable()->comment('コース（例: 芝2000）');
            $table->string('track_condition')->nullable()->comment('馬場状態');
            $table->string('finish_time')->nullable()->comment('タイム');
            $table->string('margin')->nullable()->comment('着差');
            $table->string('corner_order')->nullable()->comment('通過順');
            $table->string('pace')->nullable()->comment('ペース');
            $table->decimal('last_3f', 4, 1)->nullable()->comment('上がり');
            $table->string('horse_weight')->nullable()->comment('馬体重');
            $table->string('winner')->nullable()->comment('勝ち馬(2着馬)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horse_histories');
    }
};
