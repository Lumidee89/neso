<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->enum('type', ['credit', 'debit', 'lock', 'unlock', 'release', 'withdrawal', 'service_charge', 'refund']);
            $table->decimal('amount', 15, 2);
            $table->enum('balance_type', ['available', 'locked', 'pending']);
            $table->enum('status', ['pending', 'success', 'failed', 'reversed'])->default('pending');
            $table->text('description')->nullable();
            $table->nullableMorphs('related');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
