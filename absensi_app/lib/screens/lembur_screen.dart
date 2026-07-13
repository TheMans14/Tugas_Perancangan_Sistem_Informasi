import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class LemburScreen extends StatefulWidget {
  const LemburScreen({super.key});

  @override
  State<LemburScreen> createState() => _LemburScreenState();
}

class _LemburScreenState extends State<LemburScreen> {
  bool _isLoading = true;
  List _data      = [];

  // Timer
  Timer? _timer;
  int _detik       = 0;
  bool _isRunning  = false;
  DateTime? _startTime;
  bool _isSaving   = false;

  @override
  void initState() {
    super.initState();
    _fetchLembur();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _fetchLembur() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/lembur'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      setState(() {
        _data      = body['data'];
        _isLoading = false;
      });
    }
  }

  void _startTimer() {
    setState(() {
      _isRunning = true;
      _startTime = DateTime.now();
      _detik     = 0;
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      setState(() => _detik++);
    });
  }

  void _stopTimer() async {
    _timer?.cancel();
    setState(() => _isRunning = false);

    if (_detik < 60) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Lembur minimal 1 menit'),
          backgroundColor: Colors.red,
        ),
      );
      setState(() => _detik = 0);
      return;
    }

    // Simpan lembur
    setState(() => _isSaving = true);

    final endTime      = DateTime.now();
    final durasiMenit  = _detik ~/ 60;
    final upah         = (durasiMenit / 60) * 15000;

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/lembur'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'start_time':       _startTime!.toIso8601String(),
        'end_time':         endTime.toIso8601String(),
        'duration_minutes': durasiMenit,
        'upah':             upah,
      }),
    );

    final data = jsonDecode(response.body);
    setState(() {
      _isSaving = false;
      _detik    = 0;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(data['message']),
        backgroundColor: data['success'] == true ? Colors.green : Colors.red,
      ),
    );

    if (data['success'] == true) _fetchLembur();
  }

  String _formatWaktu(int detik) {
    final h = detik ~/ 3600;
    final m = (detik % 3600) ~/ 60;
    final s = detik % 60;
    return '${h.toString().padLeft(2, '0')}:'
        '${m.toString().padLeft(2, '0')}:'
        '${s.toString().padLeft(2, '0')}';
  }

  String _formatUpah(int detik) {
    final menit = detik ~/ 60;
    final upah  = (menit / 60) * 15000;
    return 'Rp ${upah.toStringAsFixed(0)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text('Lembur',
            style: TextStyle(
                color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  // Timer card
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(32),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      children: [
                        // Timer display
                        Text(
                          _formatWaktu(_detik),
                          style: const TextStyle(
                            fontSize: 52,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'monospace',
                            letterSpacing: 4,
                          ),
                        ),
                        const SizedBox(height: 32),

                        // Tombol Start & Stop
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            ElevatedButton(
                              onPressed: _isRunning || _isSaving
                                  ? null
                                  : _startTimer,
                              style: ElevatedButton.styleFrom(
                                backgroundColor:
                                    const Color(0xFF2ECC71),
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 36, vertical: 14),
                                shape: RoundedRectangleBorder(
                                  borderRadius:
                                      BorderRadius.circular(10),
                                ),
                              ),
                              child: const Text(
                                'Start',
                                style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold),
                              ),
                            ),
                            const SizedBox(width: 16),
                            ElevatedButton(
                              onPressed: !_isRunning || _isSaving
                                  ? null
                                  : _stopTimer,
                              style: ElevatedButton.styleFrom(
                                backgroundColor:
                                    const Color(0xFFE74C3C),
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 36, vertical: 14),
                                shape: RoundedRectangleBorder(
                                  borderRadius:
                                      BorderRadius.circular(10),
                                ),
                              ),
                              child: _isSaving
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(
                                          color: Colors.white,
                                          strokeWidth: 2),
                                    )
                                  : const Text(
                                      'Stop',
                                      style: TextStyle(
                                          color: Colors.white,
                                          fontSize: 16,
                                          fontWeight: FontWeight.bold),
                                    ),
                            ),
                          ],
                        ),

                        const SizedBox(height: 24),

                        // Info total & upah
                        Row(
                          mainAxisAlignment:
                              MainAxisAlignment.spaceEvenly,
                          children: [
                            Column(
                              children: [
                                const Text('Total',
                                    style: TextStyle(
                                        color: Colors.grey,
                                        fontSize: 13)),
                                Text(
                                  '${_detik ~/ 60} menit',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16),
                                ),
                              ],
                            ),
                            Container(
                                width: 1,
                                height: 30,
                                color: Colors.grey.shade300),
                            Column(
                              children: [
                                const Text('Upah',
                                    style: TextStyle(
                                        color: Colors.grey,
                                        fontSize: 13)),
                                Text(
                                  _formatUpah(_detik),
                                  style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 16,
                                      color: Color(0xFF2ECC71)),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Riwayat lembur
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Padding(
                          padding: EdgeInsets.all(16),
                          child: Text('Riwayat Lembur',
                              style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16)),
                        ),
                        const Divider(height: 1),
                        _data.isEmpty
                            ? const Padding(
                                padding: EdgeInsets.all(20),
                                child: Center(
                                    child: Text(
                                        'Belum ada data lembur')),
                              )
                            : ListView.separated(
                                shrinkWrap: true,
                                physics:
                                    const NeverScrollableScrollPhysics(),
                                itemCount: _data.length,
                                separatorBuilder: (_, __) =>
                                    const Divider(height: 1),
                                itemBuilder: (context, index) {
                                  final item = _data[index];
                                  return ListTile(
                                    leading: CircleAvatar(
                                      backgroundColor: const Color(
                                              0xFF2ECC71)
                                          .withOpacity(0.15),
                                      child: const Icon(
                                          Icons.access_time,
                                          color: Color(0xFF2ECC71),
                                          size: 20),
                                    ),
                                    title: Text(
                                        item['tanggal'] ?? '-',
                                        style: const TextStyle(
                                            fontWeight:
                                                FontWeight.w500)),
                                    subtitle: Text(
                                        'Durasi: ${item['duration_minutes']} menit'),
                                    trailing: Text(
                                      'Rp ${item['upah']}',
                                      style: const TextStyle(
                                        color: Color(0xFF2ECC71),
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  );
                                },
                              ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}