<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     
     public function up()
{
    Schema::create('teacher_attendances', function (Blueprint $table) {
        $table->id();
        $table->date('attendance_date');
        $table->foreignId('group_id')->constrained()->onDelete('cascade');
        $table->timestamps();
    });

    Schema::create('teacher_attendance_teacher', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_attendance_id')->constrained()->onDelete('cascade');
        $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
        $table->time('arrival_time')->nullable();
        $table->time('departure_time')->nullable();
        $table->timestamps();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
