<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        // Always throw exception with null redirect to prevent route generation
        // The exception handler will handle the response format (JSON for API, redirect for web)
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            null // Always null to prevent route generation errors
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Always return null to prevent route generation
        // The exception handler will handle the response
        return null;
    }
}
