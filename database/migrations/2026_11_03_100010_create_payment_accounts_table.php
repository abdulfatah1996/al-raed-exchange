<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30)->index();
            $table->string('internal_name');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->string('beneficiary_name')->nullable();
            $table->text('details')->nullable();                 // encrypted JSON
            $table->string('status', 10)->default('active')->index();
            $table->decimal('min_amount', 18, 2)->nullable();
            $table->decimal('max_amount', 18, 2)->nullable();
            $table->decimal('daily_cap', 18, 2)->nullable();
            $table->text('instructions_ar')->nullable();
            $table->text('instructions_en')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
