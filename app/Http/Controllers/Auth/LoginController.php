<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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

        if (!Auth::attempt(['username' => $data['username'], 'password' => $data['password']], $request->boolean('remember'))) {
            return back()
                ->withErrors(['username' => 'NIP/username atau password salah.'])
                ->onlyInput('username');
        }

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
}
