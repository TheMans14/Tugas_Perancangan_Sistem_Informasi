import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import 'ganti_password_screen.dart';

class ProfilScreen extends StatefulWidget {
  const ProfilScreen({super.key});

  @override
  State<ProfilScreen> createState() => _ProfilScreenState();
}

class _ProfilScreenState extends State<ProfilScreen> {
  bool _isLoading = true;
  Map<String, dynamic> _user = {};
  final _namaController = TextEditingController();
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _fetchProfil();
  }

  Future<void> _fetchProfil() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.get(
      Uri.parse('${ApiConfig.baseUrl}/profil'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final body = jsonDecode(response.body);
      setState(() {
        _user = body['user'];
        _namaController.text = _user['nama'];
        _isLoading = false;
      });
    }
  }

  Future<void> _updateProfil() async {
    setState(() => _isSaving = true);

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/profil/update'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({'nama': _namaController.text}),
    );

    final data = jsonDecode(response.body);
    setState(() => _isSaving = false);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(data['message']),
        backgroundColor: data['success'] == true ? Colors.green : Colors.red,
      ),
    );

    if (data['success'] == true) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('nama', _namaController.text);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text(
          'Profil',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  // Avatar
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF6B73FF), Color(0xFF9B59B6)],
                      ),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 40,
                          backgroundColor: Colors.white30,
                          backgroundImage: _user['foto'] != null
                              ? NetworkImage(_user['foto'])
                              : null,
                          child: _user['foto'] == null
                              ? Text(
                                  _user['nama']
                                          ?.substring(0, 1)
                                          .toUpperCase() ??
                                      'U',
                                  style: const TextStyle(
                                    fontSize: 32,
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                  ),
                                )
                              : null,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          _user['nama'] ?? '',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          _user['jabatan'] ?? '-',
                          style: const TextStyle(
                            color: Colors.white70,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Info card
                  Container(
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
                          'Informasi Akun',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                        const SizedBox(height: 16),
                        _infoRow(Icons.badge_outlined, 'NRP',
                            _user['nrp'] ?? '-'),
                        const Divider(),
                        _infoRow(Icons.work_outline, 'Jabatan',
                            _user['jabatan'] ?? '-'),
                        const Divider(),
                        const SizedBox(height: 8),
                        const Text('Nama',
                            style: TextStyle(
                                color: Colors.grey, fontSize: 13)),
                        const SizedBox(height: 6),
                        TextField(
                          controller: _namaController,
                          decoration: InputDecoration(
                            prefixIcon:
                                const Icon(Icons.person_outline),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                            ),
                            contentPadding:
                                const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 12),
                          ),
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          height: 46,
                          child: ElevatedButton(
                            onPressed:
                                _isSaving ? null : _updateProfil,
                            style: ElevatedButton.styleFrom(
                              backgroundColor:
                                  const Color(0xFF6B73FF),
                              shape: RoundedRectangleBorder(
                                borderRadius:
                                    BorderRadius.circular(10),
                              ),
                            ),
                            child: _isSaving
                                ? const CircularProgressIndicator(
                                    color: Colors.white)
                                : const Text(
                                    'Simpan Perubahan',
                                    style: TextStyle(
                                        color: Colors.white),
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  // Tombol ganti password
                  SizedBox(
                    width: double.infinity,
                    height: 46,
                    child: OutlinedButton.icon(
                      onPressed: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                            builder: (_) =>
                                const GantiPasswordScreen()),
                      ),
                      icon: const Icon(Icons.lock_outline),
                      label: const Text('Ganti Password'),
                      style: OutlinedButton.styleFrom(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          Icon(icon, color: const Color(0xFF6B73FF), size: 20),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label,
                  style: const TextStyle(
                      color: Colors.grey, fontSize: 12)),
              Text(value,
                  style: const TextStyle(
                      fontWeight: FontWeight.w500, fontSize: 15)),
            ],
          ),
        ],
      ),
    );
  }
}