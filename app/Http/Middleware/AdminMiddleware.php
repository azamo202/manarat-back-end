<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_admin) {
            return $next($request);
        }

        return response()->json([
            'message' => 'غير مصرح لك بالوصول إلى هذه الصفحة. هذه الصفحة للمسؤولين فقط.'
        ], 403);
    }
}
