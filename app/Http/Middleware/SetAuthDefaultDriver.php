<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SetAuthDefaultDriver
{
    /**
     * 設置默認看守器
     * @param Request $request
     * @param Closure $next
     * @param string $guard
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $guard)
    {
        Auth::setDefaultDriver($guard);

        return $next($request);
    }
}
