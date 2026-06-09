<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return redirect()->guest(route('login'));
        }

        if (!$request->user()->isAdmin()) {
            return redirect()
                ->route('peta')
                ->with('error', 'Akses manajemen data hanya untuk admin.');
        }

        return $next($request);
    }
}
