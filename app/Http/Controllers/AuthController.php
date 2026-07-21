<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** SIDANG-ALUR: Menampilkan GET /login atau mengarahkan pengguna yang sudah masuk ke halaman sesuai perannya. */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()?->isAdmin() ? 'admin.index' : 'peta');
        }

        return view('auth.login');
    }

    /**
     * SIDANG-KEAMANAN: Memproses POST /login dari email dan password, lalu meregenerasi sesi setelah autentikasi berhasil.
     */
    public function login(Request $request)
    {
        // SIDANG-VALIDASI: Kredensial wajib berupa alamat email dan password berbentuk string.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        // SIDANG-KEAMANAN: Regenerasi ID sesi mencegah session fixation setelah login.
        $request->session()->regenerate();

        $defaultRoute = $request->user()?->isAdmin() ? route('admin.index') : route('peta');

        return redirect()->intended($defaultRoute);
    }

    /** SIDANG-KEAMANAN: Memproses POST /logout, mengakhiri autentikasi, serta mengganti sesi dan token CSRF. */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
