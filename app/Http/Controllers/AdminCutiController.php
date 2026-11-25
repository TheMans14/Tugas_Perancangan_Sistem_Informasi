<?php

namespace App\Http\Controllers;

use App\Cuti;
use Illuminate\Http\Request;

class AdminCutiController extends Controller
{
    public function index()
    {
        $cutis = Cuti::with('user.role')->whereHas('user.role', function ($query) {
            $query->whereIn('role', ['Admin', 'Pegawai']);
        })->orderBy('created_at', 'desc')->get();
        return view('admin.cuti_index', compact('cutis'));
    }

    public function approve($id)
    {
        $cuti = Cuti::findOrFail($id);
        abort_unless($cuti->user->hasRole(['Admin', 'Pegawai']), 403);
        if ($cuti->status !== 'pending') {
            return back()->with('error', 'Pengajuan cuti ini sudah diproses.');
        }
        $cuti->status = 'disetujui';
        $cuti->save();

        return back()->with('success', 'Pengajuan cuti disetujui.');
    }

    public function reject($id)
    {
        $cuti = Cuti::findOrFail($id);
        abort_unless($cuti->user->hasRole(['Admin', 'Pegawai']), 403);
        if ($cuti->status !== 'pending') {
            return back()->with('error', 'Pengajuan cuti ini sudah diproses.');
        }
        $cuti->status = 'ditolak';
        $cuti->save();

        return back()->with('success', 'Pengajuan cuti ditolak.');
    }
}
