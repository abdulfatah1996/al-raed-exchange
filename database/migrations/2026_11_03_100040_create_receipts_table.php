<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->default(1);
            $table->decimal('amount', 24, 8);
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_account_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('rate', 18, 8)->nullable();
            $table->decimal('fee_amount', 18, 2)->nullable();
            $table->decimal('deliver_amount', 24, 8)->nullable();
            $table->foreignId('deliver_currency_id')->nullable()->constrained('currencies')->nullOnDelete();
            $table->foreignId('delivery_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_ref', 150)->nullable()->unique();   // TXID / bank ref — never counted twice
            $table->string('sender_name', 150)->nullable();
            $table->string('receipt_file')->nullable();                 // private disk path
            $table->string('verification_status', 10)->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->string('delivery_status', 10)->default('pending')->index();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['transfer_id', 'number']);
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->string('delivery_proof_file')->nullable()->after('internal_notes');
        });
    }

    public function down(): void
    {
        Schema::table('transfers', fn (Blueprint $t) => $t->dropColumn('delivery_proof_file'));
        Schema::dropIfExists('receipts');
    }
};
