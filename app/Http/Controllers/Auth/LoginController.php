<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Batas percobaan login sebelum akun+IP dikunci sementara. Krusial di
     * aplikasi ini karena password awal SEMUA akun ASN = NIP mereka sendiri
     * (lihat resources/views/auth/login.blade.php & README) -- tanpa
     * pembatasan ini, siapapun yang tahu/menebak daftar NIP bisa mencoba
     * login bertubi-tubi ke akun yang belum sempat ganti password.
     */
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function show(Request $request): RedirectResponse|\Illuminate\View\View
    {
        if (Auth::check()) {
            return $this->redirectByRole($request->user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (!Auth::attempt(['username' => $data['username'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            return back()
                ->withErrors(['username' => 'NIP/username atau password salah.'])
                ->onlyInput('username');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return $this->redirectByRole($request->user());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole(User $user): RedirectResponse
    {
        return redirect($user->role === 'admin' ? '/' : '/saya');
    }

    /**
     * Kunci throttle digabung username (lowercased) + IP -- supaya satu
     * penyerang yang mencoba banyak NIP dari IP yang sama tetap kena batas
     * per-IP juga (bukan cuma per-akun), tanpa mengunci pengguna sah lain
     * yang kebetulan login dari IP/jaringan (mis. kantor) yang sama.
     */
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('username')).'|'.$request->ip();
    }
}
