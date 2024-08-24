<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //return get_setting('maintenance');
        if(get_setting('maintenance')){
            return response()->json([
            'status' => true,
            'maintenance' => true,
            'message' => 'System Under Maintenance We Will Available Soon'
            ],503);
        }
        return $next($request);
    }
}
