# Sistem Absensi Euphorbia

Aplikasi absensi dengan backend dan dashboard web Laravel serta aplikasi Android Flutter.

## Fitur

- Check-in/check-out dengan validasi lokasi pada aplikasi Android.
- Shift malam 19.30–02.00 WIB, termasuk pencatatan setelah tengah malam.
- Rekap kehadiran, pengajuan cuti, lembur, dan perjalanan dinas.
- Manager dapat melihat rekap, mengekspor Excel, serta menyetujui atau menolak cuti dan perjalanan dinas.
- Manager tidak diwajibkan melakukan absensi.

## Menjalankan backend

Siapkan PHP/Composer dan MySQL yang kompatibel dengan dependensi pada `composer.lock`.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Atur koneksi database, alamat IP, dan konfigurasi Firebase pada `.env`, kemudian jalankan:

```powershell
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

Jalankan seeder hanya pada database baru; seeder akun awal memasukkan data contoh.
File service account Firebase disimpan secara lokal dan ditentukan melalui `FIREBASE_CREDENTIALS`.
File tersebut, `.env`, log, dependensi, dan hasil build tidak disertakan di repository.

## Menjalankan aplikasi Flutter

Gunakan Flutter dengan Dart yang memenuhi versi pada `absensi_app/pubspec.yaml` dan Android NDK `27.0.12077973`.
Sesuaikan URL API di `absensi_app/lib/config/api_config.dart` dengan IP komputer yang menjalankan backend.
Untuk mengakses backend dari HP melalui Wi-Fi, gunakan jaringan yang sama.

```powershell
cd absensi_app
flutter pub get
flutter run
```

## Akun contoh pada database baru

| Role | NRP | Password awal |
| --- | --- | --- |
| Admin | `123456789` | `123456789` |
| Pegawai | `001012025` | `bule123` |
| Manager | `999999999` | `manager123` |

Ganti password akun contoh sebelum digunakan di lingkungan yang dapat diakses publik.

## Pengujian backend

```powershell
php vendor/phpunit/phpunit/phpunit --filter 'PerjalananDinasTest|ManagerAccessTest|WebAttendanceAccessTest|AttendanceShiftTest'
```

Pengujian fitur tersebut menggunakan SQLite dalam memori sesuai `phpunit.xml`.
