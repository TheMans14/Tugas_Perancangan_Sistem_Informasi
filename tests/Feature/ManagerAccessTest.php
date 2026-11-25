<?php

namespace Tests\Feature;

use App\Cuti;
use App\Present;
use App\User;
use App\Http\Middleware\VerifyCsrfToken;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ManagerAccessTest extends TestCase
{
    private $admin;
    private $employee;
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id'); $table->string('role');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id'); $table->integer('role_id');
            $table->string('nama'); $table->string('nrp')->unique();
            $table->string('password'); $table->string('foto')->default('default.jpg');
            $table->timestamps();
        });
        Schema::create('presents', function (Blueprint $table) {
            $table->increments('id'); $table->integer('user_id'); $table->date('tanggal');
            $table->string('keterangan'); $table->time('jam_masuk')->nullable(); $table->time('jam_keluar')->nullable();
        });
        Schema::create('cutis', function (Blueprint $table) {
            $table->increments('id'); $table->integer('user_id');
            $table->date('tanggal_mulai'); $table->date('tanggal_selesai');
            $table->string('alasan'); $table->string('status'); $table->timestamps();
        });
        foreach (['Admin', 'Pegawai', 'Manager'] as $role) {
            $roleId = DB::table('roles')->insertGetId(['role' => $role]);
            $user = User::create(['role_id' => $roleId, 'nama' => $role, 'nrp' => str_repeat((string) $roleId, 9), 'password' => bcrypt('test12345')]);
            if ($role === 'Admin') { $this->admin = $user; }
            elseif ($role === 'Pegawai') { $this->employee = $user; }
            else { $this->manager = $user; }
        }
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Carbon::setTestNow(Carbon::parse('2026-11-25 19:55:00'));
        config(['absensi.jam_masuk' => '19:30:00', 'absensi.jam_pulang' => '02:00:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function leaveFor(User $user)
    {
        return Cuti::create(['user_id' => $user->id, 'tanggal_mulai' => '2026-11-26', 'tanggal_selesai' => '2026-11-27', 'alasan' => 'Keperluan keluarga', 'status' => 'pending']);
    }

    public function testOnlyManagerCanApproveAndRejectAdminAndEmployeeLeave()
    {
        $employeeLeave = $this->leaveFor($this->employee);
        $adminLeave = $this->leaveFor($this->admin);
        foreach ([$this->admin, $this->employee] as $user) {
            $this->actingAs($user)->get('/admin/cuti')->assertStatus(403);
            $this->post('/admin/cuti/' . $employeeLeave->id . '/approve')->assertStatus(403);
            $this->post('/admin/cuti/' . $adminLeave->id . '/reject')->assertStatus(403);
        }
        $this->assertSame('pending', $employeeLeave->fresh()->status);
        $this->withoutExceptionHandling();
        $this->actingAs($this->manager)->get('/admin/cuti')->assertOk()->assertSee('Pengajuan Cuti User dan Admin');
        $this->post('/admin/cuti/' . $employeeLeave->id . '/approve')->assertRedirect();
        $this->post('/admin/cuti/' . $adminLeave->id . '/reject')->assertRedirect();
        $this->assertSame('disetujui', $employeeLeave->fresh()->status);
        $this->assertSame('ditolak', $adminLeave->fresh()->status);
        $this->post('/admin/cuti/' . $employeeLeave->id . '/reject')->assertRedirect();
        $this->assertSame('disetujui', $employeeLeave->fresh()->status);
    }

    public function testManagerReportIncludesAdminAndUserAndCanBeFiltered()
    {
        $this->withoutExceptionHandling();
        foreach ([$this->admin, $this->employee] as $user) {
            Present::create(['user_id' => $user->id, 'tanggal' => '2026-11-25', 'keterangan' => 'Masuk', 'jam_masuk' => '19:30:00', 'jam_keluar' => '02:00:00']);
        }
        $this->actingAs($this->manager)->get('/home')->assertRedirect(route('manager.rekap'));
        $response = $this->get('/manager/rekap?bulan=2026-11');
        $response->assertOk()->assertSee('6 jam 30 menit')->assertDontSee('Ajukan Cuti')->assertDontSee('Check-in');
        $this->assertCount(2, $response->viewData('presents'));
        $filtered = $this->get('/manager/rekap?bulan=2026-11&user_id=' . $this->admin->id)->assertOk();
        $this->assertCount(1, $filtered->viewData('presents'));
        $this->assertSame($this->admin->id, (int) $filtered->viewData('presents')->first()->user_id);
        $this->get('/manager/rekap?bulan=2026-10')->assertOk()->assertSee('Belum ada data absensi');
        $this->get('/manager/rekap/export?bulan=2026-11')->assertOk();
        $this->withExceptionHandling();
        $this->actingAs($this->employee)->get('/manager/rekap')->assertStatus(403);
        $this->actingAs($this->admin)->get('/manager/rekap')->assertStatus(403);
    }

    public function testManagerCannotAttendOrSubmitLeaveWhileAdminStillCan()
    {
        $existing = Present::create(['user_id' => $this->admin->id, 'tanggal' => '2026-11-25', 'keterangan' => 'Masuk', 'jam_masuk' => '19:30:00']);
        $this->actingAs($this->manager)->post('/absen')->assertStatus(403);
        $this->post('/absen/pulang/' . $existing->id)->assertStatus(403);
        $this->get('/cuti/create')->assertStatus(403);
        $this->post('/cuti/store')->assertStatus(403);
        $this->postJson('/api/checkin', ['latitude' => 0, 'longitude' => 0])->assertStatus(403);
        $this->postJson('/api/checkout/1', ['latitude' => 0, 'longitude' => 0])->assertStatus(403);
        $this->assertSame(1, Present::count());
        $this->assertSame(0, Present::whereUserId($this->manager->id)->count());
        $this->actingAs($this->admin)->get('/cuti/create')->assertOk()->assertSee('Ajukan Cuti')->assertDontSee(route('admin.cuti.index'));
        $this->from('/cuti/create')->post('/cuti/store', ['tanggal_mulai' => '2026-11-26', 'tanggal_selesai' => '2026-11-27', 'alasan' => 'Cuti admin'])->assertRedirect();
        $this->assertSame($this->admin->id, (int) Cuti::first()->user_id);
        $this->assertSame('pending', Cuti::first()->status);
    }

    public function testAdminAttendanceDoesNotMarkManagerAsAbsent()
    {
        $this->app->instance('env', 'local');
        $this->actingAs($this->admin)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->from('/home')->post('/absen', ['user_id' => $this->admin->id])->assertRedirect('/home');
        $this->assertSame(1, Present::whereUserId($this->admin->id)->count());
        $this->assertSame(1, Present::whereUserId($this->employee->id)->whereKeterangan('Alpha')->count());
        $this->assertSame(0, Present::whereUserId($this->manager->id)->count());
    }

    public function testSeederCreatesOneManagerWithoutResettingExistingPassword()
    {
        require_once database_path('seeds/ManagerAccountSeeder.php');
        $seeder = new \ManagerAccountSeeder;
        $seeder->run();
        $account = User::where('nrp', '999999999')->firstOrFail();
        $this->assertTrue($account->hasRole('Manager'));
        $this->assertTrue(password_verify('manager123', $account->password));
        $hash = bcrypt('changed123');
        $account->password = $hash;
        $account->save();
        $seeder->run();
        $this->assertSame(1, User::where('nrp', '999999999')->count());
        $this->assertSame($hash, $account->fresh()->password);
    }

    public function testManagerCanSignInThroughWebLogin()
    {
        $this->post('/login', ['nrp' => $this->manager->nrp, 'password' => 'test12345'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($this->manager);
        $this->get('/home')->assertRedirect(route('manager.rekap'));
    }
}
