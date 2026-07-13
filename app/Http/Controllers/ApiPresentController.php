<?php

namespace App\Http\Controllers;

use App\Present;
use Illuminate\Http\Request;

class ApiPresentController extends Controller
{
    // Ambil data kehadiran bulan ini
    public function index(Request $request)
    {
        $userId = auth()->user()->id;
        $bulan  = $request->bulan ?? date('m');
        $tahun  = $request->tahun ?? date('Y');

        $presents = Present::whereUserId($userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id'          => $p->id,
                    'tanggal'     => $p->tanggal,
                    'jam_masuk'   => $p->jam_masuk  ?? '-',
                    'jam_keluar'  => $p->jam_keluar ?? '-',
                    'keterangan'  => $p->keterangan,
                ];
            });

        $masuk = Present::whereUserId($userId)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->whereKeterangan('masuk')->count();
        $telat = Present::whereUserId($userId)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->whereKeterangan('telat')->count();
        $cuti  = Present::whereUserId($userId)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->whereKeterangan('cuti')->count();
        $alpha = Present::whereUserId($userId)->whereMonth('tanggal', $bulan)->whereYear('tanggal', $tahun)->whereKeterangan('alpha')->count();

        return response()->json([
            'success'   => true,
            'statistik' => compact('masuk', 'telat', 'cuti', 'alpha'),
            'data'      => $presents,
        ]);
    }

    // Check-in
    public function checkIn(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $userId  = auth()->user()->id;
        $tanggal = date('Y-m-d');
        $jam     = date('H:i:s');

        if (date('l') == 'Saturday' || date('l') == 'Sunday') {
            return response()->json([
                'success' => false,
                'message' => 'Hari libur, tidak bisa check-in',
            ], 400);
        }

        // Validasi jarak lokasi
        $kantorLat = config('absensi_lokasi.latitude');
        $kantorLng = config('absensi_lokasi.longitude');
        $radius    = config('absensi_lokasi.radius');

        $jarak = $this->hitungJarak(
            $request->latitude,
            $request->longitude,
            $kantorLat,
            $kantorLng
        );

        if ($jarak > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius kantor (jarak: ' . round($jarak) . ' meter)',
            ], 400);
        }

        $present = Present::whereUserId($userId)->whereTanggal($tanggal)->first();

        if ($present && $present->keterangan != 'Alpha') {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah check-in hari ini',
            ], 400);
        }

        if (strtotime($jam) >= strtotime(config('absensi.jam_masuk') . ' -1 hours') && strtotime($jam) <= strtotime(config('absensi.jam_masuk'))) {
            $keterangan = 'Masuk';
        } elseif (strtotime($jam) > strtotime(config('absensi.jam_masuk')) && strtotime($jam) <= strtotime(config('absensi.jam_pulang'))) {
            $keterangan = 'Telat';
        } else {
            $keterangan = 'Alpha';
        }

        $data = [
            'user_id'    => $userId,
            'tanggal'    => $tanggal,
            'jam_masuk'  => $jam,
            'keterangan' => $keterangan,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
        ];

        if ($present) {
            $present->update($data);
        } else {
            Present::create($data);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Check-in berhasil',
            'keterangan' => $keterangan,
            'jam_masuk'  => $jam,
            'jarak'      => round($jarak),
        ]);
    }

    // Hitung jarak antara 2 koordinat (formula Haversine), hasil dalam meter
    private function hitungJarak($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371000; // radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo   = deg2rad($lat2);
        $lngTo   = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    // Cek status check-in/check-out hari ini
    public function statusHariIni(Request $request)
    {
        $userId  = auth()->user()->id;
        $tanggal = date('Y-m-d');

        $present = Present::whereUserId($userId)->whereTanggal($tanggal)->first();

        $jamSekarang  = date('H:i:s');
        $jamMasuk     = config('absensi.jam_masuk');
        $jamPulang    = config('absensi.jam_pulang');

        // Check-in aktif: 1 jam sebelum jam masuk sampai jam pulang
        $jamMulaiCheckin = date('H:i:s', strtotime($jamMasuk . ' -1 hours'));
        $bisaCheckin  = strtotime($jamSekarang) >= strtotime($jamMulaiCheckin)
            && strtotime($jamSekarang) <= strtotime($jamPulang);

        // Check-out aktif: setelah jam pulang
        $bisaCheckout = strtotime($jamSekarang) >= strtotime($jamPulang);

        if (!$present || $present->keterangan == 'Alpha') {
            return response()->json([
                'success'        => true,
                'sudah_checkin'  => false,
                'sudah_checkout' => false,
                'bisa_checkin'   => $bisaCheckin,
                'bisa_checkout'  => $bisaCheckout,
                'jam_masuk_config'  => $jamMasuk,
                'jam_pulang_config' => $jamPulang,
            ]);
        }

        return response()->json([
            'success'           => true,
            'sudah_checkin'     => true,
            'sudah_checkout'    => $present->jam_keluar ? true : false,
            'present_id'        => $present->id,
            'jam_masuk'         => $present->jam_masuk,
            'jam_keluar'        => $present->jam_keluar,
            'keterangan'        => $present->keterangan,
            'bisa_checkin'      => $bisaCheckin,
            'bisa_checkout'     => $bisaCheckout,
            'jam_masuk_config'  => $jamMasuk,
            'jam_pulang_config' => $jamPulang,
        ]);
    }

    // Check-out
    public function checkOut(Request $request, $id)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $present = Present::findOrFail($id);

        if ($present->jam_keluar) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah check-out hari ini',
            ], 400);
        }

        $jamSekarang = date('H:i:s');
        $jamPulang   = config('absensi.jam_pulang');

        if (strtotime($jamSekarang) < strtotime($jamPulang)) {
            return response()->json([
                'success' => false,
                'message' => 'Check-out hanya bisa dilakukan setelah jam ' . substr($jamPulang, 0, 5),
            ], 400);
        }

        // Validasi jarak lokasi
        $kantorLat = config('absensi_lokasi.latitude');
        $kantorLng = config('absensi_lokasi.longitude');
        $radius    = config('absensi_lokasi.radius');

        $jarak = $this->hitungJarak(
            $request->latitude,
            $request->longitude,
            $kantorLat,
            $kantorLng
        );

        if ($jarak > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'Anda berada di luar radius kantor (jarak: ' . round($jarak) . ' meter)',
            ], 400);
        }

        $present->jam_keluar = $jamSekarang;
        $present->latitude   = $request->latitude;
        $present->longitude  = $request->longitude;
        $present->save();

        return response()->json([
            'success'    => true,
            'message'    => 'Check-out berhasil',
            'jam_keluar' => $present->jam_keluar,
        ]);
    }
}
