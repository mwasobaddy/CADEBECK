<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnderConstruction
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get('site_unlocked')) {
            return $next($request);
        }

        return response()->view('showcase.under-construction');
    }
}