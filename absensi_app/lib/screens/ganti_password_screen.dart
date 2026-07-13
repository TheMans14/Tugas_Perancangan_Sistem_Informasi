import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';

class GantiPasswordScreen extends StatefulWidget {
  const GantiPasswordScreen({super.key});

  @override
  State<GantiPasswordScreen> createState() => _GantiPasswordScreenState();
}

class _GantiPasswordScreenState extends State<GantiPasswordScreen> {
  final _passwordLamaController    = TextEditingController();
  final _passwordBaruController    = TextEditingController();
  final _konfirmasiController      = TextEditingController();
  bool _isLoading   = false;
  bool _showLama    = false;
  bool _showBaru    = false;
  bool _showKonfirm = false;

  Future<void> _gantiPassword() async {
    if (_passwordLamaController.text.isEmpty ||
        _passwordBaruController.text.isEmpty ||
        _konfirmasiController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Semua field harus diisi'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    if (_passwordBaruController.text != _konfirmasiController.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Password baru dan konfirmasi tidak cocok'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token') ?? '';

    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/ganti-password'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'password':            _passwordLamaController.text,
        'password_baru':       _passwordBaruController.text,
        'konfirmasi_password': _konfirmasiController.text,
      }),
    );

    final data = jsonDecode(response.body);
    setState(() => _isLoading = false);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(data['message']),
        backgroundColor: data['success'] == true ? Colors.green : Colors.red,
      ),
    );

    if (data['success'] == true) {
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F6FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E2A4A),
        title: const Text(
          'Ganti Password',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
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
                'Ubah Password',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              const SizedBox(height: 20),
              _passwordField(
                'Password Lama',
                _passwordLamaController,
                _showLama,
                () => setState(() => _showLama = !_showLama),
              ),
              const SizedBox(height: 14),
              _passwordField(
                'Password Baru',
                _passwordBaruController,
                _showBaru,
                () => setState(() => _showBaru = !_showBaru),
              ),
              const SizedBox(height: 14),
              _passwordField(
                'Konfirmasi Password',
                _konfirmasiController,
                _showKonfirm,
                () => setState(() => _showKonfirm = !_showKonfirm),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 46,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _gantiPassword,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF6B73FF),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  child: _isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : const Text(
                          'Simpan Password',
                          style: TextStyle(color: Colors.white),
                        ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _passwordField(String label, TextEditingController controller,
      bool show, VoidCallback onToggle) {
    return TextField(
      controller: controller,
      obscureText: !show,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.lock_outline),
        suffixIcon: IconButton(
          icon: Icon(show ? Icons.visibility_off : Icons.visibility),
          onPressed: onToggle,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
        ),
      ),
    );
  }
}