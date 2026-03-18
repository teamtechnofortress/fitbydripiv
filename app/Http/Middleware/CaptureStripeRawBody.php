<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CaptureStripeRawBody
{
    public function handle(Request $request, Closure $next)
    {
        $rawBody = file_get_contents('php://input');
        app()->instance('stripe.raw_body', $rawBody);

        return $next($request);
    }
}
