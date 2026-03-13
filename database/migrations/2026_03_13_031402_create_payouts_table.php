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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->string('bet_type')->comment('券種 (単勝/複勝/馬連/馬単/ワイド/三連複/三連単)');
            $table->string('combination')->comment('組み合わせ (1 or 1-2 or 1-2-3)');
            $table->integer('payout')->comment('払戻金額(円)');
            $table->integer('popularity')->nullable()->comment('人気');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
