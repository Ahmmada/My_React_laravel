<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->softDeletes(); // ➕ هذا يضيف عمود deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropSoftDeletes(); // 🧽 هذا يحذفه عند rollback
        });
    }
};