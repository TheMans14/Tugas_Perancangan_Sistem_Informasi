import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class PerjalananDinasScreen extends StatefulWidget {
  const PerjalananDinasScreen({super.key});

  @override
  State<PerjalananDinasScreen> createState() => _PerjalananDinasScreenState();
}

class _PerjalananDinasScreenState extends State<PerjalananDinasScreen> {
  final _formKey = GlobalKey<FormState>();
  final _kota = TextEditingController();
  final _alasan = TextEditingController();
  DateTime? _mulai;
  DateTime? _selesai;
  List<Map<String, dynamic>> _trips = [];
  String? _error;
  String? _nextPage;
  bool _loading = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _kota.dispose();
    _alasan.dispose();
    super.dispose();
  }

  Future<Map<String, String>> _headers() async {
    final prefs = await SharedPreferences.getInstance();
    return {
      'Authorization': 'Bearer ${prefs.getString('token') ?? ''}',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };
  }

  Future<void> _load({bool more = false}) async {
    if (!mounted) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final url = more ? _nextPage! : '${ApiConfig.baseUrl}/perjalanan-dinas';
      final response = await http
          .get(Uri.parse(url), headers: await _headers())
          .timeout(const Duration(seconds: 20));
      if (response.statusCode != 200) {
        throw Exception(
          response.statusCode == 401
              ? 'Sesi berakhir. Silakan login kembali.'
              : 'Riwayat belum dapat dimuat.',
        );
      }
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      final rows = List<Map<String, dynamic>>.from(data['data'] as List);
      if (!mounted) return;
      setState(() {
        _trips = more ? [..._trips, ...rows] : rows;
        _nextPage = data['next_page_url'] as String?;
      });
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is Exception
              ? error.toString().replaceFirst('Exception: ', '')
              : 'Tidak dapat terhubung ke server.',
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _display(DateTime value) =>
      '${value.day.toString().padLeft(2, '0')}/${value.month.toString().padLeft(2, '0')}/${value.year} '
      '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';

  String _apiTime(DateTime value) =>
      '${value.year}-${value.month.toString().padLeft(2, '0')}-${value.day.toString().padLeft(2, '0')} '
      '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}:00';

  Future<void> _pick(bool start) async {
    final now = DateTime.now();
    final current = (start ? _mulai : _selesai) ?? _mulai ?? now;
    final date = await showDatePicker(
      context: context,
      initialDate: current.isBefore(now) ? now : current,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: DateTime(now.year + 5, 12, 31),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(current),
    );
    if (time == null || !mounted) return;
    final value = DateTime(
      date.year,
      date.month,
      date.day,
      time.hour,
      time.minute,
    );
    setState(() {
      if (start) {
        _mulai = value;
      } else {
        _selesai = value;
      }
    });
  }

  void _message(String text, {bool success = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(text),
        backgroundColor: success ? Colors.green : Colors.red,
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_mulai == null || _selesai == null) {
      _message('Pilih estimasi waktu berangkat dan selesai.');
      return;
    }
    if (_mulai!.isBefore(DateTime.now())) {
      _message('Waktu berangkat tidak boleh sudah lewat.');
      return;
    }
    if (!_selesai!.isAfter(_mulai!)) {
      _message('Waktu selesai harus setelah waktu berangkat.');
      return;
    }
    setState(() => _saving = true);
    try {
      final response = await http
          .post(
            Uri.parse('${ApiConfig.baseUrl}/perjalanan-dinas'),
            headers: await _headers(),
            body: jsonEncode({
              'kota': _kota.text.trim(),
              'alasan': _alasan.text.trim(),
              'mulai_at': _apiTime(_mulai!),
              'selesai_at': _apiTime(_selesai!),
            }),
          )
          .timeout(const Duration(seconds: 20));
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      if (!mounted) return;
      if (response.statusCode == 201 && data['success'] == true) {
        _message(
          'Pengajuan dikirim, menunggu persetujuan Manager.',
          success: true,
        );
        _kota.clear();
        _alasan.clear();
        setState(() {
          _mulai = null;
          _selesai = null;
        });
        await _load();
      } else {
        final errors = data['errors'];
        _message(
          errors is Map && errors.isNotEmpty
              ? (errors.values.first as List).first.toString()
              : data['message']?.toString() ?? 'Pengajuan belum dapat dikirim.',
        );
      }
    } catch (_) {
      if (mounted) {
        _message(
          'Koneksi bermasalah. Periksa riwayat sebelum mencoba mengirim ulang.',
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final minutes = _mulai != null && _selesai != null
        ? _selesai!.difference(_mulai!).inMinutes
        : 0;
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        title: const Text(
          'Perjalanan Dinas',
          style: TextStyle(color: Colors.white),
        ),
        backgroundColor: const Color(0xFF1E2A4A),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: RefreshIndicator(
        onRefresh: () => _load(),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text(
                        'Ajukan Perjalanan Dinas',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 18,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Estimasi waktu dalam WIB. Persetujuan diberikan oleh Manager.',
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _kota,
                        maxLength: 100,
                        decoration: const InputDecoration(
                          labelText: 'Kota tujuan',
                          border: OutlineInputBorder(),
                        ),
                        validator: (value) =>
                            value == null || value.trim().isEmpty
                            ? 'Isi kota tujuan.'
                            : null,
                      ),
                      OutlinedButton.icon(
                        onPressed: _saving ? null : () => _pick(true),
                        icon: const Icon(Icons.event),
                        label: Text(
                          _mulai == null
                              ? 'Pilih estimasi berangkat'
                              : 'Berangkat: ${_display(_mulai!)}',
                        ),
                      ),
                      OutlinedButton.icon(
                        onPressed: _saving ? null : () => _pick(false),
                        icon: const Icon(Icons.event_available),
                        label: Text(
                          _selesai == null
                              ? 'Pilih estimasi selesai'
                              : 'Selesai: ${_display(_selesai!)}',
                        ),
                      ),
                      if (minutes > 0)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          child: Text(
                            'Estimasi durasi: ${minutes ~/ 60} jam ${minutes % 60} menit',
                          ),
                        ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _alasan,
                        maxLines: 3,
                        maxLength: 2000,
                        decoration: const InputDecoration(
                          labelText: 'Alasan / keperluan dinas',
                          border: OutlineInputBorder(),
                        ),
                        validator: (value) =>
                            value == null || value.trim().isEmpty
                            ? 'Isi alasan perjalanan dinas.'
                            : null,
                      ),
                      ElevatedButton(
                        onPressed: _saving ? null : _submit,
                        child: Text(
                          _saving ? 'Mengirim...' : 'Kirim Pengajuan',
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Riwayat Perjalanan Dinas',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            if (_loading)
              const Padding(
                padding: EdgeInsets.all(16),
                child: Center(child: CircularProgressIndicator()),
              ),
            if (_error != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text(_error!),
                      TextButton(
                        onPressed: _loading ? null : () => _load(),
                        child: const Text('Coba lagi'),
                      ),
                    ],
                  ),
                ),
              ),
            if (!_loading && _error == null && _trips.isEmpty)
              const Padding(
                padding: EdgeInsets.all(20),
                child: Text('Belum ada pengajuan perjalanan dinas.'),
              ),
            ..._trips.map((trip) {
              final status = trip['status'];
              final color = status == 'disetujui'
                  ? Colors.green
                  : status == 'ditolak'
                  ? Colors.red
                  : Colors.orange;
              final label = status == 'disetujui'
                  ? 'Disetujui'
                  : status == 'ditolak'
                  ? 'Ditolak'
                  : 'Menunggu';
              final duration = (trip['estimasi_menit'] as num).toInt();
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        trip['kota'].toString(),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      Chip(
                        label: Text(label),
                        labelStyle: TextStyle(color: color),
                      ),
                      Text(
                        'Berangkat: ${_display(DateTime.parse(trip['mulai_at']))} WIB',
                      ),
                      Text(
                        'Selesai: ${_display(DateTime.parse(trip['selesai_at']))} WIB',
                      ),
                      Text(
                        'Durasi: ${duration ~/ 60} jam ${duration % 60} menit',
                      ),
                      const SizedBox(height: 8),
                      Text(trip['alasan'].toString()),
                    ],
                  ),
                ),
              );
            }),
            if (_nextPage != null)
              TextButton(
                onPressed: _loading ? null : () => _load(more: true),
                child: const Text('Muat riwayat lainnya'),
              ),
          ],
        ),
      ),
    );
  }
}
