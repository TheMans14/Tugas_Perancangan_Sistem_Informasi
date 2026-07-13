<?php

namespace App\Http\Controllers;

use App\Lembur;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiLemburController extends Controller
{
    // Ambil riwayat lembur
    public function index(Request $request)
    {
        $userId  = auth()->user()->id;
        $lemburs = Lembur::where('user_id', $userId)
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($l) {
                return [
                    'id'               => $l->id,
                    'start_time'       => $l->start_time,
                    'end_time'         => $l->end_time,
                    'duration_minutes' => $l->duration_minutes,
                    'upah'             => $l->upah,
                    'tanggal'          => Carbon::parse($l->start_time)->format('d/m/Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $lemburs,
        ]);
    }

    // Tambah lembur baru
    public function store(Request $request)
    {
        $request->validate([
            'start_time'       => 'required|date',
            'end_time'         => 'required|date|after:start_time',
            'duration_minutes' => 'required|numeric|min:1',
            'upah'             => 'required|numeric|min:0',
        ]);

        $start = Carbon::parse($request->start_time)->format('Y-m-d H:i:s');
        $end   = Carbon::parse($request->end_time)->format('Y-m-d H:i:s');

        Lembur::create([
            'user_id'          => auth()->user()->id,
            'start_time'       => $start,
            'end_time'         => $end,
            'duration_minutes' => $request->duration_minutes,
            'upah'             => $request->upah,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lembur berhasil dicatat!',
        ]);
    }
}