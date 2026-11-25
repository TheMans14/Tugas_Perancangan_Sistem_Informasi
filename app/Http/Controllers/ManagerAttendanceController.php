<?php

namespace App\Http\Controllers;

use App\Cuti;
use App\Present;
use App\User;
use App\Exports\ManagerAttendanceExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ManagerAttendanceController extends Controller
{
    private function attendance(Request $request)
    {
        $request->validate(['bulan' => 'nullable|date_format:Y-m', 'user_id' => 'nullable|integer|exists:users,id']);
        $bulan = $request->bulan ?: date('Y-m');
        list($year, $month) = explode('-', $bulan);
        $query = Present::with('user.role')->whereYear('tanggal', $year)->whereMonth('tanggal', $month)
            ->whereHas('user.role', function ($q) { $q->whereIn('role', ['Admin', 'Pegawai']); });
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        return [$bulan, $query];
    }

    public function index(Request $request)
    {
        list($bulan, $query) = $this->attendance($request);
        $summary = (clone $query)->selectRaw('LOWER(keterangan) as status, COUNT(*) as total')
            ->groupBy('keterangan')->get()->pluck('total', 'status');
        $presents = $query->orderBy('tanggal', 'desc')->orderBy('user_id')->paginate(20)->appends($request->query());
        $users = User::with('role')->whereHas('role', function ($q) {
            $q->whereIn('role', ['Admin', 'Pegawai']);
        })->orderBy('nama')->get();
        $pendingCuti = Cuti::where('status', 'pending')->whereHas('user.role', function ($q) {
            $q->whereIn('role', ['Admin', 'Pegawai']);
        })->count();
        return view('manager.rekap', compact('bulan', 'summary', 'presents', 'users', 'pendingCuti'));
    }

    public function export(Request $request)
    {
        list($bulan, $query) = $this->attendance($request);
        return Excel::download(new ManagerAttendanceExport($query->orderBy('tanggal', 'desc')->orderBy('user_id')->get()), 'rekap-absensi-' . $bulan . '.xlsx');
    }
}
