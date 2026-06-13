<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['admin', 'super_admin'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ruxsat yo\'q'], 403);
            }
            Auth::logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Sizga kirish ruxsati yo\'q']);
        }

        return $next($request);
    }
}
