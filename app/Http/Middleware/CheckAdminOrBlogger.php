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

        // Full Admin access
        if (in_array($user->role, ['super_admin', 'admin', 'child_admin'])) {
            return $next($request);
        }

        // Blogger / Content Writer access: Only allowed for Blogs, Uploads, Categories (read-only), Profile
        if (in_array($user->role, ['blogger', 'content_writer', 'blog_manager', 'blog_editor'])) {
            $path = $request->path(); // e.g. "api/admin/v1/blogs", "api/admin/v1/upload"

            // Allowed patterns for Blogger
            $isBlogRoute = str_contains($path, 'blogs');
            $isUploadRoute = str_contains($path, 'upload');
            $isCategoriesRoute = str_contains($path, 'categories') && $request->isMethod('GET');
            $isProfileRoute = str_contains($path, 'profile') || str_contains($path, 'auth/user');

            if ($isBlogRoute || $isUploadRoute || $isCategoriesRoute || $isProfileRoute) {
                return $next($request);
            }

            return response()->json([
                'status' => false,
                'message' => 'Access denied. Your employee account is restricted to Blog management only.'
            ], 403);
        }

        // Other non-admin roles (customer, dropshipper)
        return response()->json([
            'status' => false,
            'message' => 'Access denied. Administrator privileges required.'
        ], 403);
    }
}
