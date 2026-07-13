<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class ApiNotificationController extends Controller
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    // Kirim notifikasi ke satu user berdasarkan user_id
    public function kirimKeUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'judul'   => 'required|string',
            'pesan'   => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak memiliki FCM token',
            ], 400);
        }

        $message = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification(
                Notification::create($request->judul, $request->pesan)
            );

        $this->messaging->send($message);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dikirim',
        ]);
    }

    // Kirim notifikasi ke semua user (broadcast)
    public function kirimKeSemuaUser(Request $request)
    {
        $request->validate([
            'judul' => 'required|string',
            'pesan' => 'required|string',
        ]);

        $users = User::whereNotNull('fcm_token')->get();

        foreach ($users as $user) {
            try {
                $message = CloudMessage::withTarget('token', $user->fcm_token)
                    ->withNotification(
                        Notification::create($request->judul, $request->pesan)
                    );
                $this->messaging->send($message);
            } catch (\Exception $e) {
                // Skip kalau token user tidak valid
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dikirim ke semua user',
        ]);
    }
}