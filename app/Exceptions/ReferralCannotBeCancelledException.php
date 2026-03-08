<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReferralCannotBeCancelledException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'referral_not_cancellable',
        ], 422);
    }
}
