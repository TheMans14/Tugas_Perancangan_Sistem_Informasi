<?php

namespace App\Http\Controllers;

use App\Cuti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiCutiController extends Controller
{
    // Ambil riwayat cuti
    public function index(Request $request)
    {
        $cutis = Cuti::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($c) {
                return [
                    'id'               => $c->id,
                    'tanggal_mulai'    => $c->tanggal_mulai,
                    'tanggal_selesai'  => $c->tanggal_selesai,
                    'alasan'           => $c->alasan,
                    'status'           => $c->status,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $cutis,
        ]);
    }

    // Ajukan cuti baru
    public function store(Request $request)
    {
        abort_if(auth()->user()->hasRole('Manager'), 403, 'Manager tidak mengajukan cuti.');
        $request->validate([
            'tanggal_mulai'   => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan'          => 'required|string|max:255',
        ]);

        Cuti::create([
            'user_id'         => Auth::id(),
            'tanggal_mulai'   => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'alasan'          => $request->alasan,
            'status'          => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dikirim',
        ]);
    }
}
