<?php

namespace App\Http\Controllers;

use App\Present;
use App\Support\AttendanceShift;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (auth()->user()->hasRole('Manager')) {
            return redirect()->route('manager.rekap');
        }
        $shiftDate = AttendanceShift::date(now());
        $bisaCheckin = AttendanceShift::canCheckIn(now());
        $present = Present::whereUserId(auth()->user()->id)->whereTanggal($shiftDate)->first();
        $bisaCheckout = $present && AttendanceShift::canCheckOut(now(), $present->tanggal);
        $url = 'https://kalenderindonesia.com/api/' . config('absensi.api_key') . '/libur/masehi/' . date('Y/m', strtotime($shiftDate));
        $response = @file_get_contents($url);
        $kalender = json_decode($response, true);

        $libur = false;
        $holiday = null;

        if (is_array($kalender) && !empty($kalender['data'])) {
            if ($kalender['data']['holidays']) {
                foreach ($kalender['data']['holidays'] as $key => $value) {
                    if ($value['masehi'] == $shiftDate) {
                        $holiday = $value['holiday']['name'];
                        $libur = true;
                        break;
                    }
                }
            }
        }
        return view('home', compact('present', 'libur', 'holiday', 'shiftDate', 'bisaCheckin', 'bisaCheckout'));
    }
}
