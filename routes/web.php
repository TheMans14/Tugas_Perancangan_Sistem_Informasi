<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PresentsController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\AdminCutiController;
use App\Http\Controllers\LemburController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', 'AuthController@index')->name('auth.index')->middleware('guest');
Route::post('/login', 'AuthController@login')->name('login')->middleware('guest');

Route::get('/home', 'HomeController@index')->name('home');

Route::group(['middleware' => ['web', 'auth', 'roles']], function(){
    Route::post('/logout', 'AuthController@logout')->name('logout');
    Route::get('/ganti-password', 'UsersController@gantiPassword')->name('ganti-password');
    Route::patch('/update-password/{user}', 'UsersController@updatePassword')->name('update-password');
    Route::get('/profil', 'UsersController@profil')->name('profil');
    Route::patch('/update-profil/{user}', 'UsersController@updateProfil')->name('update-profil');

    Route::group(['roles' => 'Admin'], function(){
        Route::post('/users/import-jabatan', [UsersController::class, 'importJabatan'])->name('users.importJabatan');
        Route::get('/users/cari', 'UsersController@search')->name('users.search');
        Route::patch('/users/password/{user}', 'UsersController@password')->name('users.password');
        Route::resource('/users', 'UsersController');

        Route::get('/kehadiran', 'PresentsController@index')->name('kehadiran.index');
        Route::get('/kehadiran/cari', 'PresentsController@search')->name('kehadiran.search');
        Route::get('/kehadiran/{user}/cari', 'PresentsController@cari')->name('kehadiran.cari');
        Route::get('/kehadiran/excel-users', 'PresentsController@excelUsers')->name('kehadiran.excel-users');
        Route::get('/kehadiran/{user}/excel-user', 'PresentsController@excelUser')->name('kehadiran.excel-user');
        Route::post('/kehadiran/ubah', 'PresentsController@ubah')->name('ajax.get.kehadiran');
        Route::patch('/kehadiran/{kehadiran}', 'PresentsController@update')->name('kehadiran.update');
        Route::post('/kehadiran', 'PresentsController@store')->name('kehadiran.store');
    });

    Route::group(['roles' => 'Pegawai'], function(){
        Route::get('/daftar-hadir', 'PresentsController@show')->name('daftar-hadir');
        Route::get('/daftar-hadir/cari', 'PresentsController@cariDaftarHadir')->name('daftar-hadir.cari');
    });

    // ATUR IP ADDRESS DISINI
    // Route::group(['middleware' => ['ipcheck:'.config('absensi.ip_address')]], function() {
    //     Route::patch('/absen/{kehadiran}', 'PresentsController@checkOut')->name('kehadiran.check-out');
    //     Route::post('/absen', 'PresentsController@checkIn')->name('kehadiran.check-in');
    // });
    Route::group(['roles' => ['Admin', 'Pegawai'], 'middleware' => ['ipcheck:'.config('absensi.ip_address')]], function() {
        Route::post('/absen', 'PresentsController@checkIn')->name('kehadiran.check-in');
        Route::post('/absen/pulang/{kehadiran}', [PresentsController::class, 'checkOut'])->name('kehadiran.check-out');
    });
    Route::middleware(['auth', 'checkRole:karyawan', 'ipCheck'])->group(function () {
});

Route::group(['roles' => ['Admin', 'Pegawai']], function () {
    Route::get('/perjalanan-dinas', 'PerjalananDinasController@index')->name('perjalanan-dinas.index');
    Route::post('/perjalanan-dinas', 'PerjalananDinasController@store')->name('perjalanan-dinas.store');
    // Pengajuan cuti user
    Route::get('/cuti/riwayat', [CutiController::class, 'riwayat'])->name('cuti.riwayat');
    Route::get('/cuti/create', [CutiController::class, 'create'])->name('cuti.create');
    Route::post('/cuti/store', [CutiController::class, 'store'])->name('cuti.store');
});

Route::group(['roles' => 'Manager'], function () {
    Route::get('/manager/perjalanan-dinas', 'PerjalananDinasController@reviewIndex')->name('manager.perjalanan-dinas.index');
    Route::post('/manager/perjalanan-dinas/{perjalananDinas}/approve', 'PerjalananDinasController@approve')->name('manager.perjalanan-dinas.approve');
    Route::post('/manager/perjalanan-dinas/{perjalananDinas}/reject', 'PerjalananDinasController@reject')->name('manager.perjalanan-dinas.reject');
    Route::get('/manager/rekap', 'ManagerAttendanceController@index')->name('manager.rekap');
    Route::get('/manager/rekap/export', 'ManagerAttendanceController@export')->name('manager.rekap.export');
    Route::get('/admin/cuti', [AdminCutiController::class, 'index'])->name('admin.cuti.index');
    Route::post('/admin/cuti/{id}/approve', [AdminCutiController::class, 'approve'])->name('admin.cuti.approve');
    Route::post('/admin/cuti/{id}/reject', [AdminCutiController::class, 'reject'])->name('admin.cuti.reject');
});

});

Route::group(['middleware' => ['auth', 'roles'], 'roles' => ['Admin', 'Pegawai']], function () {
    Route::get('/lembur', [LemburController::class, 'create'])->name('lembur.create');
    Route::post('/lembur', [LemburController::class, 'store'])->name('lembur.store');
});
