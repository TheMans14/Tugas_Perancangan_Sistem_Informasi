import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class AjukanCutiScreen extends StatefulWidget {
  const AjukanCutiScreen({super.key});

  @override
  State<AjukanCutiScreen> createState() => _AjukanCutiScreenState();
}

class _AjukanCutiScreenState extends State<AjukanCutiScreen> {
  final _alasanController = TextEditingController();
  DateTime? _tanggalMulai;
  DateTime? _tanggalSelesai;
  bool _isSaving = false;

  Future<void> _pickDate(bool isMulai) async {
    final date = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime(2030),
    );
    if (date == null) return;

    setState(() {
      if (isMulai) {
        _tanggalMulai = date;
      } else {
        _tanggalSelesai = date;
      }
    });
  }

  String _formatDate(DateTime? date) {
    if (date == null) return 'Pilih tanggal';
    return '${date.day.toString().padLeft(2, '0')}/'
        '${date.month.toString().padLeft(2, '0')}/'
        '${date.year}';
  }

  Future<void> _ajukanCuti() async {
    if (_tanggalMulai == null ||
        _tanggalSelesai == null ||
        _alasanController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Semua field harus diisi'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    if (_tanggalSelesai!.isBefore(_tanggalMulai!)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Tanggal selesai harus setelah tanggal mulai'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isSaving = true);

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/cuti'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'tanggal_mulai': _tanggalMulai!.toIso8601String().split('T')[0],
        'tanggal_selesai':
            _tanggalSelesai!.toIso8601String().split('T')[0],
        'alasan': _alasanController.text,
      }),
    );

    final data = jsonDecode(response.body);
    setState(() => _isSaving = false);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(data['message'] ?? 'Terjadi kesalahan'),
        backgroundColor: data['success'] == true ? Colors.green : Colors.red,
      ),
    );

    if (data['success'] == true) {
      setState(() {
        _tanggalMulai   = null;
        _tanggalSelesai = null;
        _alasanController.clear();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text('Ajukan Cuti',
            style: TextStyle(
                color: Colors.white, fontWeight: FontWeight.bold)),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Form Pengajuan Cuti',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              const SizedBox(height: 20),

              // Tanggal mulai
              GestureDetector(
                onTap: () => _pickDate(true),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today,
                          color: Color(0xFFE74C3C)),
                      const SizedBox(width: 10),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Tanggal Mulai',
                              style: TextStyle(
                                  fontSize: 12, color: Colors.grey)),
                          Text(_formatDate(_tanggalMulai),
                              style: const TextStyle(
                                  fontWeight: FontWeight.w500)),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),

              // Tanggal selesai
              GestureDetector(
                onTap: () => _pickDate(false),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade300),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.event_available,
                          color: Color(0xFF2ECC71)),
                      const SizedBox(width: 10),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Tanggal Selesai',
                              style: TextStyle(
                                  fontSize: 12, color: Colors.grey)),
                          Text(_formatDate(_tanggalSelesai),
                              style: const TextStyle(
                                  fontWeight: FontWeight.w500)),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),

              // Alasan
              TextField(
                controller: _alasanController,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: 'Alasan Cuti',
                  alignLabelWithHint: true,
                  prefixIcon: const Padding(
                    padding: EdgeInsets.only(bottom: 40),
                    child: Icon(Icons.edit_note),
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton(
                  onPressed: _isSaving ? null : _ajukanCuti,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFE74C3C),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: _isSaving
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text('Ajukan Cuti',
                          style: TextStyle(color: Colors.white)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}