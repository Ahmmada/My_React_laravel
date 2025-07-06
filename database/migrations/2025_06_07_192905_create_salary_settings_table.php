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
    Schema::create('salary_settings', function (Blueprint $table) {
        $table->id();
        $table->decimal('default_hourly_rate', 8, 2)->default(1000.00); // ✅ القيمة الافتراضية
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_settings');
    }
};
