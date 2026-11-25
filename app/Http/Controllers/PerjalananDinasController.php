<?php

namespace App\Http\Controllers;

use App\PerjalananDinas;
use Illuminate\Http\Request;

class PerjalananDinasController extends Controller
{
    private function applicant(Request $request)
    {
        abort_unless($request->user()->hasRole(['Admin', 'Pegawai']), 403);
    }

    public function index(Request $request)
    {
        $this->applicant($request);
        $trips = PerjalananDinas::where('user_id', $request->user()->id)->latest()->paginate(15);
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $trips->getCollection()->map(function ($trip) {
                return $trip->apiData();
            }), 'next_page_url' => $trips->nextPageUrl()]);
        }
        return view('perjalanan_dinas.index', compact('trips'));
    }

    public function store(Request $request)
    {
        $this->applicant($request);
        $data = $request->validate([
            'kota' => 'required|string|max:100',
            'mulai_at' => 'required|date|after_or_equal:now',
            'selesai_at' => 'required|date|after:mulai_at',
            'alasan' => 'required|string|max:2000',
        ]);
        $data['user_id'] = $request->user()->id;
        $trip = PerjalananDinas::create($data)->fresh();
        $message = 'Pengajuan perjalanan dinas dikirim, menunggu persetujuan Manager.';
        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message, 'data' => $trip->apiData()], 201)
            : redirect()->route('perjalanan-dinas.index')->with('success', $message);
    }

    public function reviewIndex(Request $request)
    {
        abort_unless($request->user()->hasRole('Manager'), 403);
        $trips = PerjalananDinas::with('user.role', 'reviewer')->latest()->paginate(20);
        return view('perjalanan_dinas.review', compact('trips'));
    }

    public function approve(Request $request, PerjalananDinas $perjalananDinas)
    {
        return $this->decide($request, $perjalananDinas, 'disetujui');
    }

    public function reject(Request $request, PerjalananDinas $perjalananDinas)
    {
        return $this->decide($request, $perjalananDinas, 'ditolak');
    }

    private function decide(Request $request, PerjalananDinas $trip, $status)
    {
        abort_unless($request->user()->hasRole('Manager'), 403);
        // A conditional update prevents two reviews from overwriting one another.
        $changed = PerjalananDinas::whereKey($trip->id)->where('status', 'pending')->update([
            'status' => $status, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
        ]);
        $message = $changed ? 'Pengajuan perjalanan dinas ' . $status . '.' : 'Pengajuan ini sudah diproses.';
        return $request->expectsJson()
            ? response()->json(['success' => (bool) $changed, 'message' => $message], $changed ? 200 : 409)
            : back()->with($changed ? 'success' : 'error', $message);
    }
}
