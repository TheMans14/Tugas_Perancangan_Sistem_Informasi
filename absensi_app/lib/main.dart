import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:firebase_core/firebase_core.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  if (kIsWeb) {
    await Firebase.initializeApp(
      options: const FirebaseOptions(
        apiKey: "AIzaSyBRR56Y8462BDkPM6V32DNjeORpIH3puxY",
        authDomain: "absensi-euphorbia.firebaseapp.com",
        projectId: "absensi-euphorbia",
        storageBucket: "absensi-euphorbia.firebasestorage.app",
        messagingSenderId: "473541374434",
        appId: "1:473541374434:web:8d5f79522b166446d049bb",
        measurementId: "G-63EPW7WCNZ",
      ),
    );
  } else {
    await Firebase.initializeApp();
  }

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Absensi App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6B73FF),
        ),
        useMaterial3: true,
      ),
      initialRoute: '/login',
      routes: {
        '/login': (context) => const LoginScreen(),
        '/home':  (context) => const HomeScreen(),
      },
    );
  }
}