<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->index();
            $table->string('status', 30)->default('new')->index();

            $table->decimal('send_amount', 24, 8)->nullable();
            $table->foreignId('send_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('deliver_amount', 24, 8)->nullable();
            $table->foreignId('deliver_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('rate', 18, 8)->nullable();
            $table->decimal('fee_amount', 18, 2)->nullable();
            $table->foreignId('fee_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->decimal('commission_amount', 18, 2)->nullable();

            $table->foreignId('delivery_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();

            $table->string('sender_name', 150)->nullable();
            $table->string('beneficiary_name', 150)->nullable();
            $table->text('beneficiary_details')->nullable();           // encrypted JSON
            $table->string('external_ref', 150)->nullable()->index();  // TXID / bank reference

            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('priced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('priced_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();

            $table->text('internal_notes')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transfer_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_status_logs');
        Schema::dropIfExists('transfers');
    }
};
