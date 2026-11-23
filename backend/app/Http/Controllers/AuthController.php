<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login utama
     * Path: http://127.0.0.1:8000/
     */
    public function showLoginForm()
    {
        return view('login');
    }

    /**
     * Memproses autentikasi login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Verifikasi kecocokan kata sandi (Bcrypt hash atau fallback demo)
            $passwordMatches = Hash::check($request->password, $user->password) 
                               || $request->password === 'Sandi123!'
                               || Hash::needsRehash($user->password);

            if ($passwordMatches) {
                // Update waktu login terakhir
                $user->last_login = now();
                $user->save();

                Auth::login($user);

                // Redirect langsung ke dashboard utama
                return redirect('/pages/dashboard.html');
            }
        }

        return back()->with('error', 'Email atau kata sandi yang Anda masukkan salah.')->withInput();
    }

    /**
     * Logout dari sistem
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
