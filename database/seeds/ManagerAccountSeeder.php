<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManagerAccountSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            $role = DB::table('roles')->where('role', 'Manager')->first();
            $roleId = $role ? $role->id : DB::table('roles')->insertGetId(['role' => 'Manager']);
            $existing = DB::table('users')->where('nrp', '999999999')->first();
            if ($existing) {
                if ((int) $existing->role_id !== (int) $roleId) {
                    throw new RuntimeException('NRP Manager sudah digunakan akun lain.');
                }
                return;
            }
            DB::table('users')->insert([
                'role_id' => $roleId, 'nama' => 'Manager', 'nrp' => '999999999',
                'foto' => 'default.jpg', 'password' => Hash::make('manager123'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
