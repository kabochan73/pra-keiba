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
        Schema::create('race_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->string('rank')->comment('着順 (1,2,3...or 中止/除外)');
            $table->integer('gate_number')->nullable()->comment('枠番');
            $table->integer('horse_number')->comment('馬番');
            $table->string('horse_name')->comment('馬名');
            $table->string('sex_age')->nullable()->comment('性齢');
            $table->decimal('burden_weight', 4, 1)->nullable()->comment('斤量');
            $table->string('jockey')->nullable()->comment('騎手名');
            $table->string('finish_time')->nullable()->comment('タイム (1:23.4)');
            $table->string('margin')->nullable()->comment('着差');
            $table->string('corner_order')->nullable()->comment('コーナー通過順');
            $table->decimal('last_3f', 4, 1)->nullable()->comment('上がり3F');
            $table->decimal('win_odds', 8, 1)->nullable()->comment('単勝オッズ');
            $table->integer('popularity')->nullable()->comment('人気');
            $table->string('horse_weight')->nullable()->comment('馬体重と増減');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_results');
    }
};
