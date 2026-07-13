<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lembur;

class LemburController extends Controller
{
    public function create()
{
    return view('lembur.index');
}

public function store(Request $request)
{
    $request->validate([
        'start_time' => 'required|date',
        'end_time' => 'required|date|after:start_time',
        'duration_minutes' => 'required|numeric|min:1',
        'upah' => 'required|numeric|min:15000',
    ]);

    // Konversi format ISO 8601 ke Y-m-d H:i:s
    $start = \Carbon\Carbon::parse($request->start_time)->format('Y-m-d H:i:s');
    $end   = \Carbon\Carbon::parse($request->end_time)->format('Y-m-d H:i:s');

    Lembur::create([
        'user_id' => auth()->id(),
        'start_time' => $start,
        'end_time' => $end,
        'duration_minutes' => $request->duration_minutes,
        'upah' => $request->upah,
    ]);

    return redirect()->route('lembur.create')->with('success', 'Lembur berhasil dicatat!');
}
}
