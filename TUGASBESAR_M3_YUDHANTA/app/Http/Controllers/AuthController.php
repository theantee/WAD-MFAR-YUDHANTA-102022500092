<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(
            ['email' => ['required', 'string', 'email'], 'password' => ['required', 'string']],
            [
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'password.required' => 'Password wajib diisi.',
            ],
        );

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();   // ganti ID sesi; isi sesi (termasuk keranjang) tetap terbawa

            return redirect()->intended(route('home'));
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
            [
                'name.required' => 'Nama wajib diisi.',
                'name.max' => 'Nama maksimal 100 karakter.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email maksimal 255 karakter.',
                'email.unique' => 'Email sudah terdaftar. Gunakan email lain atau masuk.',
                'password.required' => 'Password wajib diisi.',
                'password.min' => 'Password minimal 8 karakter.',
            ],
        );

        User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);

        return redirect()->route('login')->with('status', 'Akun berhasil dibuat. Silakan masuk.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
