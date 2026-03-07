<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->bearerToken();
        $expected = config('services.api_key');

        if (! $provided || ! $expected || ! hash_equals($expected, $provided)) {
            return response()->json([
                'message' => 'Unauthorized.',
                'error' => 'unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
