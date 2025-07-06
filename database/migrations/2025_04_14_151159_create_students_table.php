<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('students', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // اسم الطالب
        $table->date('birth_date')->nullable(); // تاريخ الميلاد
        $table->string('address')->nullable();// عنوان السكن
        $table->string('phone')->nullable(); // الهاتف
        $table->text('notes')->nullable(); // ملاحظات
        $table->foreignId('center_id')->constrained()->onDelete('cascade'); // المركز
        $table->foreignId('level_id')->constrained()->onDelete('cascade'); // المستوى
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
