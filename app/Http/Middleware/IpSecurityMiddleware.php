<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\IpLog;
use Carbon\Carbon;

class IpSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        
        $ipLog = IpLog::firstOrCreate(['ip_address' => $ip]);

        if ($ipLog->is_banned) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your IP has been banned due to suspicious activity.'
            ], 403);
        }

        // Simple rate limiting logic
        $now = Carbon::now();
        if ($ipLog->last_request_at && $now->diffInSeconds($ipLog->last_request_at) < 1) {
            $ipLog->increment('request_count');
        } else {
            // Reset if more than 1 second passed? 
            // Or just cumulative? Let's do cumulative with threshold.
            $ipLog->increment('request_count');
        }

        $ipLog->last_request_at = $now;
        
        // Threshold: 1000 requests per IP (reset manually or by cron)
        // Or if you want "Bot protection", check for rapid bursts.
        if ($ipLog->request_count > 5000) {
            $ipLog->is_banned = true;
            $ipLog->ban_reason = 'Too many requests';
        }

        $ipLog->save();

        return $next($request);
    }
}
