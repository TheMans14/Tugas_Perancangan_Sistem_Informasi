<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApiPresentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route publik
Route::post('/login', 'AuthController@apiLogin');

// Route yang butuh token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/perjalanan-dinas', 'PerjalananDinasController@index');
    Route::post('/perjalanan-dinas', 'PerjalananDinasController@store');
    Route::post('/logout', 'AuthController@apiLogout');
    Route::get('/me',     'AuthController@apiMe');

    // Kehadiran
    Route::get('/kehadiran',        'ApiPresentController@index');
    Route::post('/checkin',         'ApiPresentController@checkIn');
    Route::get('/status-checkin', 'ApiPresentController@statusHariIni');
    Route::post('/checkout/{id}',   'ApiPresentController@checkOut');

    // Profil & Password
    Route::get('/profil',           'ApiUserController@profil');
    Route::post('/profil/update',   'ApiUserController@updateProfil');
    Route::post('/ganti-password',  'ApiUserController@gantiPassword');

    // Lembur
    Route::get('/lembur',          'ApiLemburController@index');
    Route::post('/lembur',         'ApiLemburController@store');

    // Cuti
    Route::get('/cuti',   'ApiCutiController@index');
    Route::post('/cuti',  'ApiCutiController@store');

    Route::post('/fcm-token', 'ApiUserController@saveFcmToken');

    // Notifikasi (khusus admin)
    Route::post('/notif/user',   'ApiNotificationController@kirimKeUser');
    Route::post('/notif/semua',  'ApiNotificationController@kirimKeSemuaUser');
});
