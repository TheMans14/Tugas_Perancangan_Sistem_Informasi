<?php

namespace App\Http\Controllers;

use App\Rules\LoginRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nrp'       => ['required', 'size:9', 'required_with:password', new LoginRule($request->password)],
            'password'  => ['required']
        ]);

        return redirect('/home');
    }

    public function logout()
    {
        auth()->logout();
        return redirect()->route('login');
    }

    public function apiLogin(Request $request)
    {
        $request->validate([
            'nrp'      => ['required'],
            'password' => ['required'],
        ]);

        // Gunakan auth()->attempt() langsung seperti LoginRule
        if (!auth()->attempt(['nrp' => $request->nrp, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'NRP atau password salah',
            ], 401);
        }

        $user  = auth()->user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'      => $user->id,
                'nama'    => $user->nama,
                'nrp'     => $user->nrp,
                'jabatan' => $user->jabatan ?? '-',
            ],
        ]);
    }

    public function apiLogout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function apiMe(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user(),
        ]);
    }
}
