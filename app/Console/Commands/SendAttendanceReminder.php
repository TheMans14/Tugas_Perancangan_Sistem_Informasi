<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class SendAttendanceReminder extends Command
{
    protected $signature = 'attendance:remind {type : masuk atau pulang}';

    protected $description = 'Kirim notifikasi pengingat check-in/check-out ke semua user';

    public function handle(Messaging $messaging)
    {
        $type    = $this->argument('type');
        $tanggal = date('Y-m-d');

        if ($type === 'masuk') {
            $userIds = User::whereNotNull('fcm_token')
                ->whereDoesntHave('presents', function ($q) use ($tanggal) {
                    $q->whereDate('tanggal', $tanggal)->whereNotNull('jam_masuk');
                })
                ->pluck('fcm_token');

            $judul = 'Pengingat Jam Masuk';
            $pesan = 'Jam masuk 1 jam lagi, jangan lupa check-in!';
        } else {
            $userIds = User::whereNotNull('fcm_token')
                ->whereHas('presents', function ($q) use ($tanggal) {
                    $q->whereDate('tanggal', $tanggal)
                      ->whereNotNull('jam_masuk')
                      ->whereNull('jam_keluar');
                })
                ->pluck('fcm_token');

            $judul = 'Pengingat Jam Pulang';
            $pesan = 'Sudah waktunya pulang, jangan lupa check-out!';
        }

        foreach ($userIds as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create($judul, $pesan));
                $messaging->send($message);
            } catch (\Throwable $e) {
                continue;
            }
        }

        $this->info("Reminder '{$type}' terkirim ke " . count($userIds) . " user.");
    }
}
