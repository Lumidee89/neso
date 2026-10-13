<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('source_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('allocated_amount', 15, 2);
            $table->string('transaction_code')->unique();
            $table->enum('status', ['pending', 'otp_sent', 'approved', 'rejected', 'released'])->default('pending');
            $table->timestamp('vendor_otp_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_allocations');
    }
};
