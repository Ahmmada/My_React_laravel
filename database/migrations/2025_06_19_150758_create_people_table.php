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
               Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_male');
            $table->boolean('is_beneficiary');
            $table->date('birth_date')->nullable();
            $table->foreignId('card_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('card_number')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('job')->nullable();
            $table->foreignId('housing_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('housing_address')->nullable();
            $table->foreignId('center_id')->nullable()->constrained()->nullOnDelete();// المستهدفة
            $table->foreignId('social_state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('level_state_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('male_count')->default(0);
            $table->unsignedInteger('female_count')->default(0);
            $table->unsignedInteger('meal_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
