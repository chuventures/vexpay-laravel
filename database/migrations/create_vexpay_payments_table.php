<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(config('vexpay.payments.table', 'vexpay_payments'), function (Blueprint $table) {
            $table->id();
            // Switch to uuidMorphs()/ulidMorphs() if your billable models use UUID/ULID keys.
            $table->morphs('billable');
            $table->string('checkout_session_id')->unique();
            $table->string('payment_id')->nullable()->unique();
            $table->string('reference', 64)->index();
            $table->decimal('amount_usd', 12, 2);
            $table->decimal('amount_ves', 18, 2)->nullable();
            $table->decimal('bcv_rate', 18, 8)->nullable();
            $table->string('method', 32)->nullable();
            $table->string('status', 16)->default('PENDING')->index();
            $table->string('failure_code')->nullable();
            $table->boolean('livemode')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('vexpay.payments.table', 'vexpay_payments'));
    }
};
