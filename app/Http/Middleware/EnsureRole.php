<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect('/login');
        }

        if ($roles !== [] && ! in_array(Auth::user()->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
            }

            abort(403, 'Anda tidak memiliki akses.');
        }

        return $next($request);
    }
}
