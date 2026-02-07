<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 1. REGISTER (Untuk membuat user admin pertama kali)
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User berhasil dibuat',
            'data' => $user,
        ], 201);
    }

    // 2. LOGIN (Inti fitur)
    public function login(Request $request)
{
    // 1. Validasi NIK
    $request->validate([
        'nik' => 'required',
        'password' => 'required'
    ]);

    // 2. Cek Credential (NIK & Password)
    // Auth::attempt otomatis mencari kolom 'nik' jika kita spesifikasikan
	$credentials = ['nik' => $request->nik, 'password' => $request->password];
    if (!Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'Login Gagal. Cek NIK dan Password.'
        ], 401);
    }

    // 3. Jika Sukses, Buat Token
    $user = Auth::user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'status' => true,
        'message' => 'Login Berhasil',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'nik' => $user->nik,
            'role' => $user->role, // <--- Penting: Kirim role ke Frontend
        ]
    ]);
}

    // 3. LOGOUT
    public function logout(Request $request)
    {
        // Hapus token yang sedang dipakai saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout Berhasil'
        ]);
    }
}