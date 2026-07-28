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

        $ip = $request->ip();
        
        // Find or create visitor
        $visitor = Visitor::where('ip_address', $ip)->first();

        if (!$visitor) {
            $country = null;
            $city = null;
            $location = null;

            // Fetch location from IP
            try {
                // In local environments, IP might be 127.0.0.1, skip lookup
                if ($ip !== '127.0.0.1' && $ip !== '::1') {
                    $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
                    if ($response->successful() && $response['status'] === 'success') {
                        $country = $response['country'] ?? null;
                        $city = $response['city'] ?? null;
                        $location = ($city ? $city . ', ' : '') . $country;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch location for IP {$ip}: " . $e->getMessage());
            }

            $visitor = Visitor::create([
                'ip_address' => $ip,
                'country' => $country,
                'city' => $city,
                'location' => $location
            ]);
        }

        // Record page view
        VisitorPageView::create([
            'visitor_id' => $visitor->id,
            'url' => $request->url,
        ]);

        return response()->json(['status' => 'success']);
    }
}
