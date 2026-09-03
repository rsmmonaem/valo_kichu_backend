<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminOrBlogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Full Admin access for Super Admin
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        $path = $request->path(); // e.g. "api/admin/v1/orders", "api/admin/v1/blogs", "api/admin/v1/staff"

        // Staff / User Management is strictly Super Admin or users with 'users' permission
        if (str_contains($path, 'staff')) {
            if ($user->role === 'super_admin' || $user->hasPermission('users')) {
                return $next($request);
            }
            return response()->json([
                'status' => false,
                'message' => 'Access denied. Only Super Administrators can manage staff users.'
            ], 403);
        }

        // Admin role has access to operational routes
        if ($user->role === 'admin' || $user->role === 'child_admin') {
            return $next($request);
        }

        // Profile, Auth User & Uploads are accessible to all authenticated staff
        if (str_contains($path, 'profile') || str_contains($path, 'auth/user') || str_contains($path, 'upload')) {
            return $next($request);
        }

        // Check module permissions dynamically
        if (str_contains($path, 'blogs') && $user->hasPermission('blogs')) {
            return $next($request);
        }

        if ((str_contains($path, 'products') || str_contains($path, 'brands') || str_contains($path, 'banners')) && $user->hasPermission('products')) {
            return $next($request);
        }

        // Categories: write requires 'products', read-only is allowed for blogs
        if (str_contains($path, 'categories') || str_contains($path, 'sub-categories') || str_contains($path, 'sub-sub-categories')) {
            if ($user->hasPermission('products') || ($request->isMethod('GET') && $user->hasPermission('blogs'))) {
                return $next($request);
            }
        }

        if ((str_contains($path, 'orders') || str_contains($path, 'shipping-methods')) && $user->hasPermission('orders')) {
            return $next($request);
        }

        if ((str_contains($path, 'customers') || str_contains($path, 'checkout-leads') || str_contains($path, 'visitors')) && ($user->hasPermission('customers') || $user->hasPermission('orders'))) {
            return $next($request);
        }

        if (str_contains($path, 'reports') && ($user->hasPermission('reports') || $user->hasPermission('orders'))) {
            return $next($request);
        }

        if (str_contains($path, 'dropshipping') && $user->hasPermission('dropshippers')) {
            return $next($request);
        }

        if ((str_contains($path, 'settings') || str_contains($path, 'page-settings') || str_contains($path, 'ip-logs')) && $user->hasPermission('settings')) {
            return $next($request);
        }

        return response()->json([
            'status' => false,
            'message' => 'Access denied. You do not have permission to access this module.'
        ], 403);
    }
}
