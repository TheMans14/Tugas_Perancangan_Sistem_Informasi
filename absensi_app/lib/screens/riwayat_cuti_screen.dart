import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class RiwayatCutiScreen extends StatefulWidget {
  const RiwayatCutiScreen({super.key});

  @override
  State<RiwayatCutiScreen> createState() => _RiwayatCutiScreenState();
}

class _RiwayatCutiScreenState extends State<RiwayatCutiScreen> {
  bool _isLoading = true;
  List _data = [];

  @override
  void initState() {
    super.initState();
    _fetchCuti();
  }

  Future<void> _fetchCuti() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/cuti'),
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

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'disetujui': return const Color(0xFF2ECC71);
      case 'ditolak':   return const Color(0xFFE74C3C);
      default:          return const Color(0xFFF39C12);
    }
  }

  IconData _statusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'disetujui': return Icons.check_circle_outline;
      case 'ditolak':   return Icons.cancel_outlined;
      default:          return Icons.hourglass_empty;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text('Riwayat Cuti',
            style: TextStyle(
                color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _fetchCuti,
              child: _data.isEmpty
                  ? const Center(child: Text('Belum ada riwayat cuti'))
                  : ListView.builder(
                      padding: const EdgeInsets.all(16),
                      itemCount: _data.length,
                      itemBuilder: (context, index) {
                        final item = _data[index];
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Icon(
                                          _statusIcon(item['status']),
                                          color: _statusColor(
                                              item['status']),
                                          size: 20),
                                      const SizedBox(width: 8),
                                      Text(
                                        '${item['tanggal_mulai']} - ${item['tanggal_selesai']}',
                                        style: const TextStyle(
                                            fontWeight: FontWeight.w600),
                                      ),
                                    ],
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 10, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: _statusColor(item['status'])
                                          .withOpacity(0.1),
                                      borderRadius:
                                          BorderRadius.circular(20),
                                    ),
                                    child: Text(
                                      item['status'].toString().toUpperCase(),
                                      style: TextStyle(
                                        color:
                                            _statusColor(item['status']),
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                item['alasan'] ?? '-',
                                style: const TextStyle(
                                    color: Colors.grey, fontSize: 13),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}