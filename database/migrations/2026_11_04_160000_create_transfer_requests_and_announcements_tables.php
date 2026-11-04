<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Requests submitted from the public website. Staff review them before anything becomes a transfer.
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('name', 150);
            $table->string('phone', 30)->index();
            $table->string('phone_digits', 20)->index();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('operation', 20);
            $table->decimal('amount', 24, 8);
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('delivery_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receive_via', 150)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('transfer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('locale', 5)->default('ar');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('info')->index();
            $table->string('title_ar', 200);
            $table->string('title_en', 200)->nullable();
            $table->text('body_ar');
            $table->text('body_en')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('transfer_requests');
    }
};
