<?php

namespace Tests\Feature;

use App\PerjalananDinas;
use App\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerjalananDinasTest extends TestCase
{
    private $employee;
    private $other;
    private $admin;
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
            $table->bigIncrements('id'); $table->integer('role_id');
            $table->string('nrp')->unique(); $table->string('nama');
            $table->string('password'); $table->string('foto')->default('default.jpg'); $table->timestamps();
        });
        require_once database_path('migrations/2026_11_25_000001_create_perjalanan_dinas_table.php');
        (new \CreatePerjalananDinasTable)->up();
        foreach (['employee' => 'Pegawai', 'other' => 'Pegawai', 'admin' => 'Admin', 'manager' => 'Manager'] as $key => $role) {
            $roleId = DB::table('roles')->where('role', $role)->value('id');
            if (!$roleId) { $roleId = DB::table('roles')->insertGetId(['role' => $role]); }
            $this->$key = User::create(['role_id' => $roleId, 'nama' => $key, 'nrp' => $key, 'password' => 'unused']);
        }
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    private function payload()
    {
        $start = now()->addDay()->setTime(19, 30);
        return ['kota' => 'Bandung', 'mulai_at' => $start->format('Y-m-d H:i:s'),
            'selesai_at' => $start->copy()->addMinutes(390)->format('Y-m-d H:i:s'), 'alasan' => 'Pertemuan dengan klien'];
    }

    private function trip(User $user)
    {
        return PerjalananDinas::create(array_merge($this->payload(), ['user_id' => $user->id]))->fresh();
    }

    public function testEmployeeCanSubmitFromAppWithoutForgingOwnerOrApproval()
    {
        $payload = array_merge($this->payload(), ['user_id' => $this->other->id, 'status' => 'disetujui', 'reviewed_by' => $this->manager->id]);
        $response = $this->actingAs($this->employee)->postJson('/api/perjalanan-dinas', $payload);
        $response->assertStatus(201)->assertJsonPath('data.status', 'pending')->assertJsonPath('data.estimasi_menit', 390);
        $trip = PerjalananDinas::first();
        $this->assertSame($this->employee->id, (int) $trip->user_id);
        $this->assertNull($trip->reviewed_by);
        $this->assertNull($trip->reviewed_at);
        $this->getJson('/api/perjalanan-dinas')->assertOk()->assertJsonCount(1, 'data');
    }

    public function testWebSubmissionAndOwnHistory()
    {
        $other = $this->trip($this->other);
        $other->kota = 'Surabaya'; $other->save();
        $this->actingAs($this->employee)->get('/perjalanan-dinas')->assertOk()->assertSee('Ajukan Perjalanan Dinas')->assertDontSee('Surabaya');
        $this->post('/perjalanan-dinas', $this->payload())->assertRedirect(route('perjalanan-dinas.index'));
        $this->get('/perjalanan-dinas')->assertOk()->assertSee('Bandung')->assertSee('Menunggu')->assertSee('6 jam 30 menit')->assertDontSee('Surabaya');
        $this->getJson('/api/perjalanan-dinas')->assertJsonCount(1, 'data')->assertJsonPath('data.0.kota', 'Bandung');
    }

    public function testInvalidTimesAndMissingFieldsAreRejected()
    {
        $this->actingAs($this->employee)->postJson('/api/perjalanan-dinas', [])->assertStatus(422)->assertJsonValidationErrors(['kota', 'mulai_at', 'selesai_at', 'alasan']);
        $payload = $this->payload(); $payload['selesai_at'] = $payload['mulai_at'];
        $this->postJson('/api/perjalanan-dinas', $payload)->assertStatus(422)->assertJsonValidationErrors('selesai_at');
        $payload['mulai_at'] = now()->subDay()->format('Y-m-d H:i:s');
        $this->postJson('/api/perjalanan-dinas', $payload)->assertStatus(422)->assertJsonValidationErrors('mulai_at');
        $payload = $this->payload(); $payload['kota'] = str_repeat('x', 101);
        $this->postJson('/api/perjalanan-dinas', $payload)->assertStatus(422)->assertJsonValidationErrors('kota');
        $this->assertSame(0, PerjalananDinas::count());
    }

    public function testOnlyManagerCanMakeDecisionsAndDecisionsCannotBeOverwritten()
    {
        $trip = $this->trip($this->employee);
        foreach ([$this->employee, $this->other, $this->admin] as $actor) {
            $this->actingAs($actor)->get('/manager/perjalanan-dinas')->assertStatus(403);
            $this->postJson('/manager/perjalanan-dinas/' . $trip->id . '/approve')->assertStatus(403);
            $this->postJson('/manager/perjalanan-dinas/' . $trip->id . '/reject')->assertStatus(403);
        }
        $this->assertSame('pending', $trip->fresh()->status);
        $this->actingAs($this->manager)->get('/manager/perjalanan-dinas')->assertOk()->assertSee('Bandung')->assertSee('Setujui');
        $this->postJson('/manager/perjalanan-dinas/' . $trip->id . '/approve')->assertOk();
        $this->assertSame('disetujui', $trip->fresh()->status);
        $this->assertSame($this->manager->id, (int) $trip->fresh()->reviewed_by);
        $this->assertNotNull($trip->fresh()->reviewed_at);
        $this->postJson('/manager/perjalanan-dinas/' . $trip->id . '/reject')->assertStatus(409);
        $this->assertSame('disetujui', $trip->fresh()->status);
        $this->actingAs($this->employee)->getJson('/api/perjalanan-dinas')->assertJsonPath('data.0.status', 'disetujui');
        $adminTrip = $this->trip($this->admin);
        $this->actingAs($this->manager)->postJson('/manager/perjalanan-dinas/' . $adminTrip->id . '/reject')->assertOk();
        $this->assertSame('ditolak', $adminTrip->fresh()->status);
    }

    public function testUnauthenticatedAccessAndManagerSubmissionsAreDenied()
    {
        $this->getJson('/api/perjalanan-dinas')->assertStatus(401);
        $this->postJson('/api/perjalanan-dinas', $this->payload())->assertStatus(401);
        $this->actingAs($this->manager)->postJson('/api/perjalanan-dinas', $this->payload())->assertStatus(403);
        $this->get('/perjalanan-dinas')->assertStatus(403);
        $this->assertSame(0, PerjalananDinas::count());
    }
}
