<?php

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            $table->string('idempotency_key')->nullable()->unique();

            // Patient identity
            $table->string('patient_name');
            $table->date('patient_date_of_birth');
            $table->string('patient_external_id')->nullable()->index();

            // Referral details
            $table->text('referral_reason');
            $table->string('priority')->default(ReferralPriority::Medium->value);
            $table->string('status')->default(ReferralStatus::Received->value);
            $table->string('referring_party')->index();
            $table->text('notes')->nullable();

            // Triage outcome
            $table->text('triage_notes')->nullable();

            // Cancellation
            $table->string('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Composite indexes for list queries: filter + order by created_at
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
