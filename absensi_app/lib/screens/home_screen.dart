import 'dart:convert';
import 'package:flutter/material.dart';
import 'perjalanan_dinas_screen.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:geolocator/geolocator.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import '../config/api_config.dart';
import '../services/auth_service.dart';
import '../services/notification_service.dart';
import 'kehadiran_screen.dart';
import 'profil_screen.dart';
import 'lembur_screen.dart';
import 'ajukan_cuti_screen.dart';
import 'riwayat_cuti_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _authService         = AuthService();
  final _notificationService = NotificationService();
  String _nama           = '';
  String _nrp            = '';
  bool _isProcessing     = false;
  bool _isLoadingStatus  = true;

  bool _sudahCheckin      = false;
  bool _sudahCheckout     = false;
  bool _bisaCheckin       = false;
  bool _bisaCheckout      = false;
  String _jamMasuk        = '';
  String _jamKeluar       = '';
  String _jamMasukConfig  = '';
  String _jamPulangConfig = '';
  int? _presentId;

  @override
  void initState() {
    super.initState();
    _loadUser();
    _cekStatus();
    _setupNotifikasi();
  }

  Future<void> _setupNotifikasi() async {
    await _notificationService.initNotification();
    _notificationService.listenForMessages(context);

    String? token = await _notificationService.getToken();
    if (token != null) await _simpanFcmToken(token);

    FirebaseMessaging.instance.onTokenRefresh.listen((newToken) async {
      await _simpanFcmToken(newToken);
    });
  }

  Future<void> _simpanFcmToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    final authToken = prefs.getString('token') ?? '';

    try {
      await http.post(
        Uri.parse('${ApiConfig.baseUrl}/fcm-token'),
        headers: {
          'Authorization': 'Bearer $authToken',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'fcm_token': token}),
      );
    } catch (e) {}
  }

  void _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _nama = prefs.getString('nama') ?? '';
      _nrp  = prefs.getString('nrp')  ?? '';
    });
  }

  Future<void> _cekStatus() async {
    setState(() => _isLoadingStatus = true);

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    try {
      final response = await http.get(
        Uri.parse('${ApiConfig.baseUrl}/status-checkin'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _sudahCheckin       = data['sudah_checkin']     ?? false;
          _sudahCheckout      = data['sudah_checkout']    ?? false;
          _jamMasuk           = data['jam_masuk']         ?? '';
          _jamKeluar          = data['jam_keluar']        ?? '';
          _presentId          = data['present_id'];
          _bisaCheckin        = data['bisa_checkin']      ?? false;
          _bisaCheckout       = data['bisa_checkout']     ?? false;
          _jamMasukConfig     = data['jam_masuk_config']  ?? '';
          _jamPulangConfig    = data['jam_pulang_config'] ?? '';
        });
      }
    } catch (e) {}

    setState(() => _isLoadingStatus = false);
  }

  void _logout() async {
    await _authService.logout();
    Navigator.pushReplacementNamed(context, '/login');
  }

  Future<Position?> _getCurrentLocation() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Aktifkan GPS/Lokasi terlebih dahulu'),
        backgroundColor: Colors.red,
      ));
      return null;
    }

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Izin lokasi ditolak'),
          backgroundColor: Colors.red,
        ));
        return null;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Izin lokasi ditolak permanen. Aktifkan lewat Settings HP'),
        backgroundColor: Colors.red,
      ));
      return null;
    }

    return await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high);
  }

  void _checkIn() async {
    setState(() => _isProcessing = true);

    final position = await _getCurrentLocation();
    if (position == null) {
      setState(() => _isProcessing = false);
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/checkin'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'latitude':  position.latitude,
          'longitude': position.longitude,
        }),
      );

      final data = jsonDecode(response.body);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(data['message']),
        backgroundColor: data['success'] == true ? Colors.green : Colors.red,
      ));

      if (data['success'] == true) await _cekStatus();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Tidak dapat terhubung ke server'),
        backgroundColor: Colors.red,
      ));
    }

    setState(() => _isProcessing = false);
  }

  void _checkOut() async {
    if (_presentId == null) return;
    setState(() => _isProcessing = true);

    final position = await _getCurrentLocation();
    if (position == null) {
      setState(() => _isProcessing = false);
      return;
    }

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    try {
      final response = await http.post(
        Uri.parse('${ApiConfig.baseUrl}/checkout/$_presentId'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'latitude':  position.latitude,
          'longitude': position.longitude,
        }),
      );

      final data = jsonDecode(response.body);
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(data['message']),
        backgroundColor: data['success'] == true ? Colors.green : Colors.red,
      ));

      if (data['success'] == true) await _cekStatus();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
        content: Text('Tidak dapat terhubung ke server'),
        backgroundColor: Colors.red,
      ));
    }

    setState(() => _isProcessing = false);
  }

  String _formatJam(String jam) {
    if (jam.isEmpty) return '';
    return jam.substring(0, 5);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text('ABSENSI',
            style: TextStyle(
                color: Colors.white, fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            onPressed: _logout,
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _cekStatus,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                      colors: [Color(0xFF6B73FF), Color(0xFF9B59B6)]),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Selamat Datang!',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(_nama,
                        style: const TextStyle(
                            color: Colors.white70, fontSize: 16)),
                    Text('NRP: $_nrp',
                        style: const TextStyle(
                            color: Colors.white60, fontSize: 13)),
                  ],
                ),
              ),

              const SizedBox(height: 24),

              // Card absensi
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: _isLoadingStatus
                    ? const Center(
                        child: Padding(
                          padding: EdgeInsets.all(12),
                          child: CircularProgressIndicator(),
                        ),
                      )
                    : Column(
                        children: [
                          // Info jam masuk & keluar
                          if (_jamMasuk.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceEvenly,
                                children: [
                                  Column(children: [
                                    const Text('Jam Masuk',
                                        style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey)),
                                    Text(_jamMasuk,
                                        style: const TextStyle(
                                            fontWeight: FontWeight.bold)),
                                  ]),
                                  Container(
                                      width: 1,
                                      height: 28,
                                      color: Colors.grey.shade300),
                                  Column(children: [
                                    const Text('Jam Keluar',
                                        style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey)),
                                    Text(
                                        _jamKeluar.isNotEmpty
                                            ? _jamKeluar
                                            : '-',
                                        style: const TextStyle(
                                            fontWeight: FontWeight.bold)),
                                  ]),
                                ],
                              ),
                            ),

                          // Status absensi
                          if (!_sudahCheckin) ...[
                            if (_bisaCheckin) ...[
                              const Text('Silahkan Check-in',
                                  style: TextStyle(
                                      fontSize: 16, color: Colors.grey)),
                              const SizedBox(height: 4),
                              const Text(
                                '📍 Pastikan GPS aktif dan berada di area kantor',
                                style: TextStyle(
                                    fontSize: 11, color: Colors.grey),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 16),
                              SizedBox(
                                width: double.infinity,
                                height: 48,
                                child: ElevatedButton(
                                  onPressed:
                                      _isProcessing ? null : _checkIn,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor:
                                        const Color(0xFF6B73FF),
                                    shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(10)),
                                  ),
                                  child: _isProcessing
                                      ? const CircularProgressIndicator(
                                          color: Colors.white)
                                      : const Text('Check-in',
                                          style: TextStyle(
                                              color: Colors.white,
                                              fontSize: 16)),
                                ),
                              ),
                            ] else ...[
                              const Icon(Icons.access_time,
                                  color: Colors.grey, size: 36),
                              const SizedBox(height: 8),
                              Text(
                                'Check-in tersedia mulai pukul ${_formatJam(_jamMasukConfig)} - ${_formatJam(_jamPulangConfig)}',
                                style: const TextStyle(
                                    color: Colors.grey, fontSize: 13),
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ] else if (!_sudahCheckout) ...[
                            if (_bisaCheckout) ...[
                              const Text('Silahkan Check-out',
                                  style: TextStyle(
                                      fontSize: 16, color: Colors.grey)),
                              const SizedBox(height: 16),
                              SizedBox(
                                width: double.infinity,
                                height: 48,
                                child: ElevatedButton(
                                  onPressed:
                                      _isProcessing ? null : _checkOut,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor:
                                        const Color(0xFFE74C3C),
                                    shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(10)),
                                  ),
                                  child: _isProcessing
                                      ? const CircularProgressIndicator(
                                          color: Colors.white)
                                      : const Text('Check-out',
                                          style: TextStyle(
                                              color: Colors.white,
                                              fontSize: 16)),
                                ),
                              ),
                            ] else ...[
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.check_circle,
                                      color: Color(0xFF2ECC71), size: 20),
                                  const SizedBox(width: 6),
                                  Text(
                                    'Sudah check-in pukul $_jamMasuk',
                                    style: const TextStyle(
                                        color: Color(0xFF2ECC71),
                                        fontWeight: FontWeight.w500),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Check-out tersedia mulai pukul ${_formatJam(_jamPulangConfig)}',
                                style: const TextStyle(
                                    color: Colors.orange, fontSize: 12),
                                textAlign: TextAlign.center,
                              ),
                              const SizedBox(height: 16),
                              SizedBox(
                                width: double.infinity,
                                height: 48,
                                child: ElevatedButton(
                                  onPressed: null,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.grey.shade400,
                                    shape: RoundedRectangleBorder(
                                        borderRadius:
                                            BorderRadius.circular(10)),
                                  ),
                                  child: const Text('Check-out',
                                      style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 16)),
                                ),
                              ),
                            ],
                          ] else ...[
                            const Icon(Icons.check_circle,
                                color: Color(0xFF2ECC71), size: 40),
                            const SizedBox(height: 8),
                            const Text('Absensi hari ini selesai',
                                style: TextStyle(
                                    fontWeight: FontWeight.w500,
                                    color: Color(0xFF2ECC71))),
                          ],
                        ],
                      ),
              ),

              const SizedBox(height: 24),

              const Text('Menu',
                  style: TextStyle(
                      fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                children: [
                  _menuCard(Icons.check_circle_outline, 'Kehadiran',
                      const Color(0xFF6B73FF),
                      onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const KehadiranScreen()))),
                  _menuCard(Icons.access_time, 'Lembur',
                      const Color(0xFF2ECC71),
                      onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const LemburScreen()))),
                  _menuCard(Icons.calendar_today, 'Ajukan Cuti',
                      const Color(0xFFE74C3C),
                      onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const AjukanCutiScreen()))),
                  _menuCard(Icons.history, 'Riwayat Cuti',
                      const Color(0xFFF39C12),
                      onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const RiwayatCutiScreen()))),
                  _menuCard(Icons.business_center_outlined, 'Perjalanan Dinas',
                      const Color(0xFF16A085),
                      onTap: () => Navigator.push(context, MaterialPageRoute(
                          builder: (_) => const PerjalananDinasScreen()))),
                  _menuCard(Icons.person_outline, 'Profil',
                      const Color(0xFF3498DB),
                      onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const ProfilScreen()))),
                  _menuCard(Icons.lock_outline, 'Ganti Password',
                      const Color(0xFF8E44AD),
                      onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const ProfilScreen()))),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _menuCard(IconData icon, String title, Color color,
      {VoidCallback? onTap}) {
    return GestureDetector(
      onTap: onTap ?? () {},
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.grey.shade200),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 30),
            ),
            const SizedBox(height: 10),
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w500, fontSize: 14)),
          ],
        ),
      ),
    );
  }
}
