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
        Schema::create('race_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->integer('gate_number')->comment('枠番');
            $table->integer('horse_number')->comment('馬番');
            $table->string('horse_name')->comment('馬名');
            $table->string('horse_url')->nullable()->comment('馬詳細URL');
            $table->string('sex_age')->nullable()->comment('性齢 (牡3など)');
            $table->decimal('burden_weight', 4, 1)->nullable()->comment('斤量');
            $table->string('jockey')->nullable()->comment('騎手名');
            $table->string('trainer')->nullable()->comment('調教師名');
            $table->string('horse_weight')->nullable()->comment('馬体重と増減 (480(+2)など)');
            $table->decimal('win_odds', 8, 1)->nullable()->comment('単勝オッズ');
            $table->integer('popularity')->nullable()->comment('人気');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('race_entries');
    }
};
