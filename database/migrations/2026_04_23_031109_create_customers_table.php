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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_profile_id')->constrained()->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('gstin')->nullable()->index();
            $table->string('state');
            $table->string('state_code', 2)->nullable();
            $table->string('address');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('customer_type')->default('regular');
            $table->boolean('is_interstate')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
