<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiUserController extends Controller
{
    // Ambil data profil
    public function profil(Request $request)
    {
        $user = $request->user();

        $foto = null;
        if ($user->foto && $user->foto != 'default.jpg') {
            $foto = url('storage/' . $user->foto);
        }

        return response()->json([
            'success' => true,
            'user'    => [
                'id'      => $user->id,
                'nama'    => $user->nama,
                'nrp'     => $user->nrp,
                'jabatan' => $user->jabatan ?? '-',
                'foto'    => $foto,
            ],
        ]);
    }

    // Update profil
    public function updateProfil(Request $request)
    {
        $request->validate([
            'nama' => ['required', 'max:32'],
        ]);

        $user = $request->user();
        $user->nama    = $request->nama;
        $user->jabatan = $request->jabatan;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'user'    => [
                'id'      => $user->id,
                'nama'    => $user->nama,
                'nrp'     => $user->nrp,
                'jabatan' => $user->jabatan ?? '-',
            ],
        ]);
    }

    // Ganti password
    public function gantiPassword(Request $request)
    {
        $request->validate([
            'password'             => 'required|min:6',
            'password_baru'        => 'required|min:6|same:konfirmasi_password',
            'konfirmasi_password'  => 'required|min:6',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak cocok',
            ], 400);
        }

        if ($request->password == $request->password_baru) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru tidak boleh sama dengan password lama',
            ], 400);
        }

        $user->password = Hash::make($request->password_baru);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui',
        ]);
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'FCM Token berhasil disimpan',
        ]);
    }
}
