<?php

namespace Tests\Feature;

use App\Present;
use App\Support\AttendanceShift;
use App\Http\Controllers\ApiPresentController;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceShiftTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['absensi.jam_masuk' => '19:30:00', 'absensi.jam_pulang' => '02:00:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testNightShiftBoundaries()
    {
        foreach ([
            ['2026-11-25 18:29:59', false, 'Alpha'],
            ['2026-11-25 18:30:00', true, 'Masuk'],
            ['2026-11-25 19:30:00', true, 'Masuk'],
            ['2026-11-25 19:30:01', true, 'Telat'],
            ['2026-11-26 00:00:00', true, 'Telat'],
            ['2026-11-26 02:00:00', true, 'Telat'],
            ['2026-11-26 02:00:01', false, 'Alpha'],
            ['2026-11-26 12:00:00', false, 'Alpha'],
        ] as $case) {
            $now = Carbon::parse($case[0]);
            $this->assertSame($case[1], AttendanceShift::canCheckIn($now), $case[0]);
            $this->assertSame($case[2], AttendanceShift::description($now), $case[0]);
        }
        $this->assertSame('2026-11-25', AttendanceShift::date(Carbon::parse('2026-11-26 02:00:00')));
        $this->assertSame('2026-11-26', AttendanceShift::date(Carbon::parse('2026-11-26 18:30:00')));
        $this->assertFalse(AttendanceShift::canCheckOut(Carbon::parse('2026-11-25 23:59:59'), '2026-11-25'));
        $this->assertFalse(AttendanceShift::canCheckOut(Carbon::parse('2026-11-26 01:59:59'), '2026-11-25'));
        $this->assertTrue(AttendanceShift::canCheckOut(Carbon::parse('2026-11-26 02:00:00'), '2026-11-25'));
        $this->assertTrue(AttendanceShift::canCheckOut(Carbon::parse('2026-11-26 03:00:00'), '2026-11-25'));
    }

    public function testFridayShiftCanFinishOnSaturdayButSaturdayNightIsClosed()
    {
        $this->assertTrue(AttendanceShift::canCheckIn(Carbon::parse('2026-11-28 01:00:00')));
        $this->assertSame('2026-11-27', AttendanceShift::date(Carbon::parse('2026-11-28 02:00:00')));
        $this->assertFalse(AttendanceShift::canCheckIn(Carbon::parse('2026-11-28 19:30:00')));
        $this->assertFalse(AttendanceShift::canCheckIn(Carbon::parse('2026-11-29 01:00:00')));
    }

    public function testNightShiftReportsAndManualAttendance()
    {
        $this->assertSame(390, AttendanceShift::workedMinutes('19:30:00', '02:00:00'));
        $this->assertSame(360, AttendanceShift::workedMinutes('20:00:00', '02:00:00'));
        $this->assertSame('Telat', AttendanceShift::descriptionForTime('2026-11-25', '00:30:00'));
        $this->assertSame('Masuk', AttendanceShift::descriptionForTime('2026-11-25', '19:30:00'));
        $this->assertSame(5, AttendanceShift::lateHours('2026-11-25', '00:30:00'));
    }

    public function testDaytimeScheduleStillWorks()
    {
        config(['absensi.jam_masuk' => '08:00:00', 'absensi.jam_pulang' => '16:00:00']);
        $this->assertTrue(AttendanceShift::canCheckIn(Carbon::parse('2026-11-25 07:00:00')));
        $this->assertSame('Telat', AttendanceShift::description(Carbon::parse('2026-11-25 09:00:00')));
        $this->assertFalse(AttendanceShift::canCheckIn(Carbon::parse('2026-11-25 18:00:00')));
    }

    public function testApiKeepsPreviousShiftAndAllowsCheckoutOnlyAtTwo()
    {
        // This schema exists only in the in-memory test database.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role');
        });
        \Illuminate\Support\Facades\DB::table('roles')->insert(['id' => 1, 'role' => 'Pegawai']);
        Schema::create('presents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->date('tanggal');
            $table->string('keterangan');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            $table->decimal('latitude', 12, 8)->nullable();
            $table->decimal('longitude', 12, 8)->nullable();
        });
        auth()->guard()->setUser((new User)->forceFill(['id' => 1, 'role_id' => 1]));
        config(['absensi_lokasi.latitude' => 0, 'absensi_lokasi.longitude' => 0, 'absensi_lokasi.radius' => 100]);
        $present = Present::create(['user_id' => 1, 'tanggal' => '2026-11-27', 'keterangan' => 'Masuk', 'jam_masuk' => '19:30:00']);
        $controller = new ApiPresentController;
        $request = Request::create('/api/checkout/' . $present->id, 'POST', ['latitude' => 0, 'longitude' => 0]);

        Carbon::setTestNow(Carbon::parse('2026-11-27 23:00:00'));
        $this->assertSame(400, $controller->checkOut($request, $present->id)->getStatusCode());
        Carbon::setTestNow(Carbon::parse('2026-11-28 01:59:59'));
        $status = $controller->statusHariIni(new Request)->getData(true);
        $this->assertSame($present->id, $status['present_id']);
        $this->assertFalse($status['bisa_checkout']);
        $this->assertSame(400, $controller->checkOut($request, $present->id)->getStatusCode());

        Carbon::setTestNow(Carbon::parse('2026-11-28 02:00:00'));
        $this->assertTrue($controller->statusHariIni(new Request)->getData(true)['bisa_checkout']);
        $this->assertSame(200, $controller->checkOut($request, $present->id)->getStatusCode());
        $this->assertSame('2026-11-27', $present->fresh()->tanggal);
        $this->assertSame('02:00:00', $present->fresh()->jam_keluar);
        $this->assertTrue($controller->statusHariIni(new Request)->getData(true)['sudah_checkout']);
        $this->assertSame(1, Present::count());

        // A late arrival after midnight belongs to Friday, not Saturday.
        auth()->guard()->setUser((new User)->forceFill(['id' => 2, 'role_id' => 1]));
        Carbon::setTestNow(Carbon::parse('2026-11-28 01:00:00'));
        $this->assertSame(200, $controller->checkIn($request)->getStatusCode());
        $arrival = Present::whereUserId(2)->first();
        $this->assertSame('2026-11-27', $arrival->tanggal);
        $this->assertSame('Telat', $arrival->keterangan);
        $this->assertSame(400, $controller->checkIn($request)->getStatusCode());
        Carbon::setTestNow(Carbon::parse('2026-11-28 02:00:01'));
        $this->assertSame(400, $controller->checkIn($request)->getStatusCode());
        $this->assertSame(2, Present::count());
    }
}
