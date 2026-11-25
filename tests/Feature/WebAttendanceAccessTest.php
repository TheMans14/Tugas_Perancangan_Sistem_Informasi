<?php

namespace Tests\Feature;

use App\Http\Middleware\IpMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use App\Present;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WebAttendanceAccessTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testAdminCanCheckInFromLocalWebDashboard()
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('role');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id');
            $table->string('nama');
            $table->string('nrp');
            $table->string('password');
            $table->timestamps();
        });
        Schema::create('presents', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->date('tanggal');
            $table->string('keterangan');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
        });
        DB::table('roles')->insert(['id' => 1, 'role' => 'Admin']);
        $admin = User::create(['role_id' => 1, 'nama' => 'Test Admin', 'nrp' => '123456789', 'password' => 'unused']);
        $this->app->instance('env', 'local');
        config(['absensi.jam_masuk' => '19:30:00', 'absensi.jam_pulang' => '02:00:00']);
        Carbon::setTestNow(Carbon::parse('2026-11-25 19:55:00'));
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->actingAs($admin)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->from('/home')->post('/absen', ['user_id' => 999])->assertRedirect('/home');
        $this->assertSame(1, Present::count());
        $this->assertSame($admin->id, (int) Present::first()->user_id);
        $this->assertSame('Telat', Present::first()->keterangan);
        $this->assertSame('2026-11-25', Present::first()->tanggal);
    }

    public function testLocalIpv6AndConfiguredNetworkAreAllowed()
    {
        $this->app->instance('env', 'local');
        foreach (['127.0.0.1', '::1', '192.168.100.9', '192.168.100.20'] as $ip) {
            $request = Request::create('/absen', 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);
            $response = (new IpMiddleware)->handle($request, function () { return response('ok'); }, '192.168.100.*');
            $this->assertSame(200, $response->getStatusCode());
        }
    }

    public function testUnlistedIpIsStillForbidden()
    {
        $this->app->instance('env', 'local');
        $request = Request::create('/absen', 'POST', [], [], [], ['REMOTE_ADDR' => '203.0.113.1']);
        try {
            (new IpMiddleware)->handle($request, function () { $this->fail('Unexpected IP bypass'); }, '192.168.100.9');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
            return;
        }
        $this->fail('Expected HTTP 403');
    }

    public function testProductionDoesNotImplicitlyAllowLoopback()
    {
        $this->app->instance('env', 'production');
        $request = Request::create('/absen', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        try {
            (new IpMiddleware)->handle($request, function () { $this->fail('Unexpected production bypass'); }, '192.168.100.9');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
            return;
        }
        $this->fail('Expected HTTP 403');
    }
}
