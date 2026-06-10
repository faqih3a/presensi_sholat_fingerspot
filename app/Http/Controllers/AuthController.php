<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (in_array(Auth::user()->role, ['admin', 'asatidz'])) {
                return redirect()->intended('/dashboard');
            } elseif (Auth::user()->role === 'santri') {
                return redirect()->intended('/santri/dashboard');
            }
            Auth::logout();
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user is registered and has valid roles
        if (!$user || !in_array($user->role, ['admin', 'asatidz', 'santri'])) {
            return back()->withErrors([
                'email' => 'Anda belum terdaftar sebagai pengurus masjid.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            if (Auth::user()->role === 'santri') {
                return redirect()->intended('/santri/dashboard');
            }
            
            // Both roles redirect to unified dashboard
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $autoLogout = $request->input('auto_logout') === '1';

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($autoLogout) {
            return redirect()->route('login')->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.');
        }

        return redirect()->route('login');
    }
}
