<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Monolog\Processor\UidProcessor;

class RequestLog
{
    /**
     * 请求日志
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if (env('REQUEST_LOG')) {
            $headers = [];
            foreach ($request->headers as $k => $v) {
                $headers[$k] = $v[0];
            }

            Log::debug("Request ==> " . $request->method() . ' ' . $request->url(), [
                'ip' => $request->ip(),
                'post' => $request->post(),
                'headers' => $headers
            ]);
        }

        $response = $next($request);

        $response->headers->set('Request-Uid', get_logger_uid());

        return $response;
    }
}
