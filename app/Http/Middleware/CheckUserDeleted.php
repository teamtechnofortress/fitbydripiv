<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserDeleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user(); // Get the authenticated user

        // Check if user exists and the deleted field is 0
        if ($user && $user->deleted == 1) {
            return response()->json(['message' => 'Access denied. User is inactive or deleted.'], 403);
        }

        return $next($request);
    }
}
