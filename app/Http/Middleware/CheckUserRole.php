<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is an admin
        if (auth()->user() && (auth()->user()->isCompany == false)) {
            return response()->json(['error' => 'You dont have admin role.'], 403);
        }

        return $next($request); 
    }
}
