<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {        
        if($request->expectsJson()){//api request
            return null;
        }else if($request->is('backoffice') || $request->is('backoffice/*')){//backoffice request
            return route('backoffice.login');
        }else{
            return route('login');
        }
    }
}
