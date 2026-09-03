<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi route ke role tertentu ("admin" atau "asn"). Kalau user login
 * tapi rolenya beda, arahkan ke halaman "rumah"-nya sendiri (bukan 403
 * mentah) supaya tidak nyasar ke halaman kosong.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role !== $role) {
            return redirect($user->role === 'admin' ? '/' : '/saya');
        }

        return $next($request);
    }
}
