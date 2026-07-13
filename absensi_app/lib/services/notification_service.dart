import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';

class NotificationService {
  final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  Future<void> initNotification() async {
    // Minta izin notifikasi
    NotificationSettings settings = await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    if (settings.authorizationStatus == AuthorizationStatus.authorized) {
      print('Izin notifikasi diberikan');
    }

    // Ambil FCM Token (untuk dikirim ke Laravel nanti)
    String? token = await _messaging.getToken();
    print('FCM Token: $token');
  }

  Future<String?> getToken() async {
    return await _messaging.getToken();
  }

  void listenForMessages(BuildContext context) {
    // Saat aplikasi terbuka (foreground) dan notif masuk
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      if (message.notification != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${message.notification!.title}: ${message.notification!.body}',
            ),
            backgroundColor: Colors.blue,
            duration: const Duration(seconds: 4),
          ),
        );
      }
    });
  }
}