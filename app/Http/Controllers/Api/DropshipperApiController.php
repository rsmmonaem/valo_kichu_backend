<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DropshipperApiController extends Controller
{
    /**
     * Generate a new API key for the dropshipper
     */
    public function generateKey(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAnyDropshipper()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $request->name ?? 'Default Key',
            'key' => (string) Str::uuid(),
            'secret' => Str::random(40),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $apiKey
        ], 201);
    }

    /**
     * List API keys
     */
    public function index()
    {
        $keys = auth()->user()->apiKeys;
        return response()->json(['data' => $keys]);
    }

    /**
     * Toggle status or delete key
     */
    public function update(Request $request, $id)
    {
        $key = auth()->user()->apiKeys()->findOrFail($id);
        $key->update($request->only('is_active', 'name'));
        
        return response()->json(['message' => 'API Key updated']);
    }

    public function destroy($id)
    {
        $key = auth()->user()->apiKeys()->findOrFail($id);
        $key->delete();
        
        return response()->json(['message' => 'API Key deleted']);
    }

    /**
     * Get dashboard stats for the dropshipper
     */
    public function getStats()
    {
        $user = auth()->user();

        $totalProfit = WalletTransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');

        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $subDropshippers = $user->children()->count();

        // API usage is a bit tricky without user_id in IpLog, 
        // we can count total keys or just use a placeholder for now if it's not critical.
        // Let's use total hits from their active keys if possible, but IpLog is simpler for now.
        $apiUsage = ApiKey::where('user_id', $user->id)->count() . " keys";

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_profit' => $totalProfit,
                'active_orders' => $activeOrders,
                'sub_dropshippers' => $subDropshippers,
                'api_usage' => $apiUsage,
                'currency' => 'BDT' // Or from settings
            ]
        ]);
    }

    /**
     * Get orders for the dropshipper
     */
    public function getOrders()
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)
            ->latest()
            ->paginate(15);
            
        return response()->json($orders);
    }

    /**
     * Get referred children (sub-dropshippers)
     */
    public function getChildren()
    {
        $user = auth()->user();
        $children = $user->children;
        
        return response()->json([
            'status' => 'success',
            'data' => UserResource::collection($children)
        ]);
    }

    /**
     * Get wallet transaction history
     */
    public function getWallet()
    {
        $user = auth()->user();
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate(20);
            
        return response()->json($transactions);
    }

    /**
     * Get profile of the dropshipper
     */
    public function getProfile()
    {
        $user = auth()->user();
        $user->load('dropshipperProfile');
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => new UserResource($user),
                'store_name' => $user->dropshipperProfile?->name ?? ($user->first_name . ' ' . $user->last_name)
            ]
        ]);
    }

    /**
     * Update profile of the dropshipper
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|unique:users,phone_number,' . $user->id,
            'store_name' => 'sometimes|string|max:255',
            'store_logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'store_banner' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slogan' => 'sometimes|string|max:255',
            'about_us' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $userData = $request->only('first_name', 'last_name', 'email', 'phone_number');

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/users'), $imageName);
            $userData['image'] = $imageName;
        }

        $user->update($userData);

        if ($request->hasAny(['store_name', 'slogan', 'about_us'])) {
            $profileData = [];
            if ($request->has('store_name')) $profileData['name'] = $request->store_name;
            if ($request->has('slogan')) $profileData['slogan'] = $request->slogan;
            if ($request->has('about_us')) $profileData['about_us'] = $request->about_us;
            
            // Handle logo
            if ($request->hasFile('store_logo')) {
                $logoName = 'logo_' . time() . '.' . $request->store_logo->extension();
                $request->store_logo->move(public_path('storage/stores'), $logoName);
                $profileData['store_logo'] = $logoName;
            }

            // Handle banner
            if ($request->hasFile('store_banner')) {
                $bannerName = 'banner_' . time() . '.' . $request->store_banner->extension();
                $request->store_banner->move(public_path('storage/stores'), $bannerName);
                $profileData['store_banner'] = $bannerName;
            }

            $user->dropshipperProfile()->updateOrCreate(
                ['customer_id' => $user->id],
                array_merge($profileData, ['email' => $user->email, 'phone' => $user->phone_number])
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user->fresh(['dropshipperProfile']))
        ]);
    }

    /**
     * Get public store info by referral code (username)
     */
    public function getPublicStore($refer_code)
    {
        $user = User::where('refer_code', $refer_code)->firstOrFail();
        $user->load('dropshipperProfile');
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $user->dropshipperProfile?->name ?? ($user->first_name . ' ' . $user->last_name),
                'logo' => $user->dropshipperProfile?->store_logo ? asset('storage/stores/' . $user->dropshipperProfile->store_logo) : null,
                'banner' => $user->dropshipperProfile?->store_banner ? asset('storage/stores/' . $user->dropshipperProfile->store_banner) : null,
                'slogan' => $user->dropshipperProfile?->slogan,
                'about' => $user->dropshipperProfile?->about_us,
                'social' => $user->dropshipperProfile?->social_links,
            ]
        ]);
    }
}
