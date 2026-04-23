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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('legal_name');
            $table->string('gstin')->unique();
            $table->string('pan', 10);
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('state_code', 2)->nullable();
            $table->string('pincode', 6);
            $table->string('email');
            $table->string('phone', 20);
            $table->string('business_type');
            $table->date('registration_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
