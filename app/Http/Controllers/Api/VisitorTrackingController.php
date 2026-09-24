<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\VisitorPageView;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisitorTrackingController extends Controller
{
    public function track(Request $request)
    {
        $request->validate([
            'url' => 'required|string',
        ]);

        // Support Cloudflare and reverse proxies for real visitor IP
        $rawIp = $request->header('CF-Connecting-IP')
            ?? ($request->header('X-Forwarded-For') ? explode(',', $request->header('X-Forwarded-For'))[0] : null)
            ?? $request->ip();
        $ip = $rawIp ? trim($rawIp) : '127.0.0.1';

        $fbEventId = $request->input('fb_event_id');
        $fbp = $request->input('fbp');
        $fbc = $request->input('fbc');
        $userAgent = $request->input('user_agent') ?: $request->userAgent();
        $referrer = $request->input('referrer') ?: $request->header('referer');

        // Detect device type
        $deviceType = $request->input('device_type');
        if (!$deviceType && $userAgent) {
            $uaLower = strtolower($userAgent ?? '');
            if (str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet')) {
                $deviceType = 'Tablet';
            } elseif (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'android') || str_contains($uaLower, 'iphone')) {
                $deviceType = 'Mobile';
            } else {
                $deviceType = 'Desktop';
            }
        }

        // Find or create visitor
        $visitor = Visitor::where('ip_address', $ip)->first();

        if (!$visitor) {
            $country = null;
            $city = null;
            $location = null;

            // Fetch location from IP
            try {
                if ($ip !== '127.0.0.1' && $ip !== '::1' && !str_starts_with($ip, '192.168.') && !str_starts_with($ip, '10.')) {
                    $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}");
                    if ($response->successful() && ($response['status'] ?? '') === 'success') {
                        $country = $response['country'] ?? null;
                        $city = $response['city'] ?? null;
                        $location = ($city ? $city . ', ' : '') . $country;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch location for IP {$ip}: " . $e->getMessage());
            }

            $visitor = Visitor::create([
                'ip_address'      => $ip,
                'country'         => $country,
                'city'            => $city,
                'location'        => $location,
                'last_visited_at' => now(),
                'fb_event_id'     => $fbEventId,
                'fbp'             => $fbp,
                'fbc'             => $fbc,
                'device_type'     => $deviceType,
                'user_agent'      => $userAgent,
                'referrer'        => $referrer,
            ]);
        } else {
            // Update returning visitor details and last active time
            $updateData = [
                'last_visited_at' => now(),
            ];
            if ($fbEventId) $updateData['fb_event_id'] = $fbEventId;
            if ($fbp) $updateData['fbp'] = $fbp;
            if ($fbc) $updateData['fbc'] = $fbc;
            if ($deviceType) $updateData['device_type'] = $deviceType;
            if ($userAgent) $updateData['user_agent'] = $userAgent;
            if ($referrer && $referrer !== $visitor->referrer) $updateData['referrer'] = $referrer;

            $visitor->update($updateData);
            $visitor->touch();
        }

        // Record page view
        VisitorPageView::create([
            'visitor_id'  => $visitor->id,
            'url'         => $request->url,
            'fb_event_id' => $fbEventId,
        ]);

        return response()->json([
            'status'     => 'success',
            'visitor_id' => $visitor->id,
        ]);
    }
}
