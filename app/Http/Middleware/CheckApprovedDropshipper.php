<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovedDropshipper
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isAnyDropshipper() && !$user->is_approved) {
            return response()->json([
                'error' => 'Your dropshipper account is pending admin approval.',
                'is_approved' => false
            ], 403);
        }

        return $next($request);
    }
}
