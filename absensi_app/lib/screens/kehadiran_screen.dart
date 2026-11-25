import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class KehadiranScreen extends StatefulWidget {
  const KehadiranScreen({super.key});

  @override
  State<KehadiranScreen> createState() => _KehadiranScreenState();
}

class _KehadiranScreenState extends State<KehadiranScreen> {
  bool _isLoading = true;
  Map<String, dynamic> _statistik = {};
  List _data = [];
  DateTime _bulanDipilih = DateTime.now();

  @override
  void initState() {
    super.initState();
    _fetchKehadiran();
  }

  Future<void> _fetchKehadiran() async {
    setState(() => _isLoading = true);

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/kehadiran'
          '?bulan=${_bulanDipilih.month}&tahun=${_bulanDipilih.year}'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      setState(() {
        _statistik = body['statistik'];
        _data      = body['data'];
        _isLoading = false;
      });
    }
  }

  Future<void> _pilihBulan() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _bulanDipilih,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDatePickerMode: DatePickerMode.year,
      helpText: 'Pilih Bulan',
    );
    if (picked != null) {
      setState(() => _bulanDipilih = DateTime(picked.year, picked.month));
      _fetchKehadiran();
    }
  }

  static const _namaBulan = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text('Kehadiran',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _fetchKehadiran,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    // Filter bulan
                    InkWell(
                      onTap: _pilihBulan,
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calendar_month,
                                color: Color(0xFF6B73FF)),
                            const SizedBox(width: 10),
                            Text(
                              '${_namaBulan[_bulanDipilih.month - 1]} ${_bulanDipilih.year}',
                              style: const TextStyle(
                                  fontWeight: FontWeight.w500, fontSize: 15),
                            ),
                            const Spacer(),
                            const Icon(Icons.arrow_drop_down),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Statistik
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      crossAxisSpacing: 12,
                      mainAxisSpacing: 12,
                      childAspectRatio: 1.9,
                      children: [
                        _statCard('Masuk',  '${_statistik['masuk']}',  const Color(0xFF2ECC71)),
                        _statCard('Telat',  '${_statistik['telat']}',  const Color(0xFFF39C12)),
                        _statCard('Cuti',   '${_statistik['cuti']}',   const Color(0xFF9B59B6)),
                        _statCard('Alpha',  '${_statistik['alpha']}',  const Color(0xFFE74C3C)),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Tabel kehadiran
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Padding(
                            padding: EdgeInsets.all(16),
                            child: Text('Riwayat Kehadiran',
                                style: TextStyle(
                                    fontWeight: FontWeight.bold, fontSize: 16)),
                          ),
                          const Divider(height: 1),
                          _data.isEmpty
                              ? const Padding(
                                  padding: EdgeInsets.all(20),
                                  child: Center(
                                      child: Text('Belum ada data kehadiran')),
                                )
                              : ListView.separated(
                                  shrinkWrap: true,
                                  physics: const NeverScrollableScrollPhysics(),
                                  itemCount: _data.length,
                                  separatorBuilder: (_, __) =>
                                      const Divider(height: 1),
                                  itemBuilder: (context, index) {
                                    final item = _data[index];
                                    return ListTile(
                                      leading: CircleAvatar(
                                        backgroundColor: _keteranganColor(
                                                item['keterangan'])
                                            .withOpacity(0.15),
                                        child: Icon(
                                          _keteranganIcon(item['keterangan']),
                                          color: _keteranganColor(
                                              item['keterangan']),
                                          size: 20,
                                        ),
                                      ),
                                      title: Text(item['tanggal'],
                                          style: const TextStyle(
                                              fontWeight: FontWeight.w500)),
                                      subtitle: Text(
                                          'Masuk: ${item['jam_masuk']}  |  Keluar: ${item['jam_keluar']}'),
                                      trailing: Container(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: _keteranganColor(
                                                  item['keterangan'])
                                              .withOpacity(0.1),
                                          borderRadius:
                                              BorderRadius.circular(20),
                                        ),
                                        child: Text(
                                          item['keterangan'],
                                          style: TextStyle(
                                            color: _keteranganColor(
                                                item['keterangan']),
                                            fontSize: 12,
                                            fontWeight: FontWeight.w500,
                                          ),
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
            ),
    );
  }

  Widget _statCard(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 8,
            height: 36,
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(4),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label,
                    style: TextStyle(
                        fontSize: 12, color: Colors.grey.shade600)),
                const SizedBox(height: 2),
                Text(value,
                    style: const TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold),
                    overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Color _keteranganColor(String ket) {
    switch (ket.toLowerCase()) {
      case 'masuk':  return const Color(0xFF2ECC71);
      case 'telat':  return const Color(0xFFF39C12);
      case 'cuti':   return const Color(0xFF9B59B6);
      default:       return const Color(0xFFE74C3C);
    }
  }

  IconData _keteranganIcon(String ket) {
    switch (ket.toLowerCase()) {
      case 'masuk':  return Icons.check_circle_outline;
      case 'telat':  return Icons.access_time;
      case 'cuti':   return Icons.calendar_today;
      default:       return Icons.cancel_outlined;
    }
  }
}