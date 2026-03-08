<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->ulid('referral_id');
            $table->foreign('referral_id')
                ->references('id')
                ->on('referrals')
                ->cascadeOnDelete();

            $table->string('event', 32);
            $table->jsonb('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['referral_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
