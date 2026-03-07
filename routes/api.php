<?php

use App\Http\Controllers\Api\V1\ReferralController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('api.key')->group(function (): void {
    Route::get('referrals', [ReferralController::class, 'index']);
    Route::post('referrals', [ReferralController::class, 'store'])->middleware('throttle:referrals');
    Route::get('referrals/{referral}', [ReferralController::class, 'show']);
    Route::post('referrals/{referral}/cancel', [ReferralController::class, 'cancel']);
});
