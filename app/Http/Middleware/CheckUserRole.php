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
        if ($request->user() && $request->user()->roles->isEmpty()) {
            // Allow access to logout and a potential "pending" page
            if ($request->routeIs('logout') || $request->routeIs('admin.pending')) {
                return $next($request);
            }

            return redirect()->route('admin.pending');
        }

        return $next($request);
    }
}
