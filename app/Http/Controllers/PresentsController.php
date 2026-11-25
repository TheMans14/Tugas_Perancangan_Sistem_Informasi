<?php

namespace App\Http\Controllers;

use App\Present;
use App\Support\AttendanceShift;
use App\User;
use App\Exports\PresentExport;
use App\Exports\UsersPresentExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class PresentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tanggal = AttendanceShift::date(now());
        $presents = Present::with('user') // ⬅️ Tambahkan eager loading
            ->whereTanggal($tanggal)
            ->orderBy('jam_masuk', 'desc')
            ->paginate(6);

        $masuk = Present::whereTanggal($tanggal)->whereKeterangan('masuk')->count();
        $telat = Present::whereTanggal($tanggal)->whereKeterangan('telat')->count();
        $cuti = Present::whereTanggal($tanggal)->whereKeterangan('cuti')->count();
        $izin = Present::whereTanggal($tanggal)->whereKeterangan('cuti')->count();
        $alpha = Present::whereTanggal($tanggal)->whereKeterangan('alpha')->count();
        $rank = $presents->firstItem();

        return view('presents.index', compact('presents', 'rank', 'masuk', 'telat', 'cuti', 'izin', 'alpha'));
    }


    public function search(Request $request)
    {
        $request->validate([
            'tanggal' => ['required']
        ]);
        $presents = Present::whereTanggal($request->tanggal)->orderBy('jam_masuk', 'desc')->paginate(6);
        $masuk = Present::whereTanggal($request->tanggal)->whereKeterangan('masuk')->count();
        $telat = Present::whereTanggal($request->tanggal)->whereKeterangan('telat')->count();
        $cuti = \App\Cuti::where('tanggal_mulai', '<=', date('Y-m-d'))
            ->where('tanggal_selesai', '>=', date('Y-m-d'))
            ->count();
        $alpha = Present::whereTanggal($request->tanggal)->whereKeterangan('alpha')->count();
        return view('presents.index', compact('presents', 'rank', 'masuk', 'telat', 'cuti', 'izin', 'alpha'));
    }

    public function cari(Request $request, User $user)
    {
        $request->validate([
            'bulan' => ['required']
        ]);
        $data = explode('-', $request->bulan);
        $presents = Present::whereUserId($user->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->orderBy('tanggal', 'desc')->paginate(5);
        $masuk = Present::whereUserId($user->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('masuk')->count();
        $telat = Present::whereUserId($user->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('telat')->count();
        $cuti = Present::whereUserId($user->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('cuti')->count();
        $alpha = Present::whereUserId($user->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('alpha')->count();
        $kehadiran = Present::whereUserId($user->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('telat')->get();
        $totalJamTelat = 0;
        foreach ($kehadiran as $present) {
            $totalJamTelat += AttendanceShift::lateHours($present->tanggal, $present->jam_masuk);
        }
        $url = 'https://kalenderindonesia.com/api/YZ35u6a7sFWN/libur/masehi/' . date('Y/m');
        $kalender = file_get_contents($url);
        $kalender = json_decode($kalender, true);
        $libur = false;
        $holiday = null;
        if ($kalender['data'] != false) {
            if ($kalender['data']['holiday']['data']) {
                foreach ($kalender['data']['holiday']['data'] as $key => $value) {
                    if ($value['date'] == date('Y-m-d')) {
                        $holiday = $value['name'];
                        $libur = true;
                        break;
                    }
                }
            }
        }
        return view('users.show', compact('presents', 'user', 'masuk', 'telat', 'cuti', 'izin', 'alpha', 'libur', 'totalJamTelat'));
    }

    public function cariDaftarHadir(Request $request)
    {
        $request->validate([
            'bulan' => ['required']
        ]);
        $data = explode('-', $request->bulan);
        $presents = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->orderBy('tanggal', 'desc')->paginate(5);
        $masuk = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('masuk')->count();
        $telat = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('telat')->count();
        $cuti = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('cuti')->count();
        $alpha = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', $data[1])->whereYear('tanggal', $data[0])->whereKeterangan('alpha')->count();
        return view('presents.show', compact('presents', 'masuk', 'telat', 'cuti', 'alpha'));
    }

    public function checkIn(Request $request)
    {
        $users = User::whereHas('role', function ($query) {
            $query->whereIn('role', ['Admin', 'Pegawai']);
        })->get();
        $now = now();
        $data['jam_masuk']  = $now->format('H:i:s');
        $data['tanggal']    = AttendanceShift::date($now);
        $data['user_id']    = auth()->id();

        if (\Carbon\Carbon::parse($data['tanggal'])->isWeekend()) {
            return redirect()->back()->with('error', 'Hari Libur Tidak bisa Check In');
        }

        if (!AttendanceShift::canCheckIn($now)) {
            return redirect()->back()->with('error', 'Check-in belum tersedia untuk shift ini');
        }

        foreach ($users as $user) {
            $absen = Present::whereUserId($user->id)->whereTanggal($data['tanggal'])->first();
            if (!$absen) {
                if ($user->id != $data['user_id']) {
                    Present::create([
                        'keterangan'    => 'Alpha',
                        'tanggal'       => $data['tanggal'],
                        'user_id'       => $user->id
                    ]);
                }
            }
        }

        $data['keterangan'] = AttendanceShift::description($now);

        $present = Present::whereUserId($data['user_id'])->whereTanggal($data['tanggal'])->first();
        if ($present) {
            if ($present->keterangan == 'Alpha') {
                $present->update($data);
                return redirect()->back()->with('success', 'Check-in berhasil');
            } else {
                return redirect()->back()->with('error', 'Check-in gagal');
            }
        }

        Present::create($data);
        return redirect()->back()->with('success', 'Check-in berhasil');
    }

    public function checkOut(Request $request, Present $kehadiran)
    {
        abort_unless($kehadiran->user_id == auth()->id(), 403);
        if (!AttendanceShift::canCheckOut(now(), $kehadiran->tanggal)) {
            return redirect()->back()->with('error', 'Check-out hanya bisa dilakukan setelah jam ' . substr(config('absensi.jam_pulang'), 0, 5));
        }

        if ($kehadiran->jam_keluar) {
            return redirect()->back()->with('error', 'Anda sudah melakukan absen pulang.');
        }

        $kehadiran->jam_keluar = now()->format('H:i:s');
        $kehadiran->save();

        return redirect()->back()->with('success', 'Absen pulang berhasil.');
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_if(User::findOrFail($request->user_id)->hasRole('Manager'), 403, 'Manager tidak perlu melakukan absensi.');
        $tanggal = AttendanceShift::date(now());
        $present = Present::whereUserId($request->user_id)->whereTanggal($tanggal)->first();
        if ($present) {
            return redirect()->back()->with('error', 'Absensi hari ini telah terisi');
        }
        $data = $request->validate([
            'keterangan'    => ['required'],
            'user_id'    => ['required']
        ]);
        $data['tanggal'] = $tanggal;
        if ($request->keterangan == 'Masuk' || $request->keterangan == 'Telat') {
            $data['jam_masuk'] = $request->jam_masuk;
            $data['keterangan'] = AttendanceShift::descriptionForTime($data['tanggal'], $data['jam_masuk']);
        }
        Present::create($data);
        return redirect()->back()->with('success', 'Kehadiran berhasil ditambahkan');
    }

    public function ubah(Request $request)
    {
        $present = Present::findOrFail($request->id);
        echo json_encode($present);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $presents = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', date('m'))->whereYear('tanggal', date('Y'))->orderBy('tanggal', 'desc')->paginate(6);
        $masuk = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', date('m'))->whereYear('tanggal', date('Y'))->whereKeterangan('masuk')->count();
        $telat = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', date('m'))->whereYear('tanggal', date('Y'))->whereKeterangan('telat')->count();
        $cuti = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', date('m'))->whereYear('tanggal', date('Y'))->whereKeterangan('cuti')->count();
        $alpha = Present::whereUserId(auth()->user()->id)->whereMonth('tanggal', date('m'))->whereYear('tanggal', date('Y'))->whereKeterangan('alpha')->count();
        return view('presents.show', compact('presents', 'masuk', 'telat', 'cuti', 'alpha'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Present  $kehadiran
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Present $kehadiran)
    {
        abort_if($kehadiran->user->hasRole('Manager'), 403, 'Manager tidak perlu melakukan absensi.');
        $data = $request->validate([
            'keterangan'    => ['required']
        ]);

        if ($request->jam_keluar) {
            $data['jam_keluar'] = $request->jam_keluar;
        }

        if ($request->keterangan == 'Masuk' || $request->keterangan == 'Telat') {
            $data['jam_masuk'] = $request->jam_masuk;
            $data['keterangan'] = AttendanceShift::descriptionForTime($kehadiran->tanggal, $data['jam_masuk']);
        } else {
            $data['jam_masuk'] = null;
            $data['jam_keluar'] = null;
        }
        $kehadiran->update($data);
        return redirect()->back()->with('success', 'Kehadiran tanggal "' . date('l, d F Y', strtotime($kehadiran->tanggal)) . '" berhasil diubah');
    }

    public function excelUser(Request $request, User $user)
    {
        return Excel::download(new PresentExport($user->id, $request->bulan), 'kehadiran-' . $user->nrp . '-' . $request->bulan . '.xlsx');
    }

    public function excelUsers(Request $request)
    {
        return Excel::download(new UsersPresentExport($request->tanggal), 'kehadiran-' . $request->tanggal . '.xlsx');
    }
}
