<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email dan password wajib diisi.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
                return response()->json(['message' => 'Email atau password tidak sesuai.'], 422);
            }

            $request->session()->regenerate();

            return response()->json([
                'message' => 'Berhasil masuk.',
                'redirect' => '/dashboard',
            ]);
        } catch (\Throwable $e) {
            Log::error('Login gagal: '.$e->getMessage());

            return response()->json(['message' => 'Terjadi kesalahan saat masuk.'], 500);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Anda sudah keluar.', 'redirect' => '/login']);
    }
}
