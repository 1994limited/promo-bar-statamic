<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection()
    {
        return config('promo-bar.drivers.database.connection') ?: parent::getConnection();
    }

    public function up(): void
    {
        Schema::create(config('promo-bar.drivers.database.table', 'promo_bars'), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('order')->default(0)->index();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('promo-bar.drivers.database.table', 'promo_bars'));
    }
};
