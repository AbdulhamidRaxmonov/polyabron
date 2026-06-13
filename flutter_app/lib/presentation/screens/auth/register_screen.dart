import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/repositories/auth_repository.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/app_snackbar.dart';

class RegisterScreen extends StatefulWidget {
  final String phone;
  const RegisterScreen({super.key, required this.phone});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _passwordController = TextEditingController();
  final _otpController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;

  @override
  void dispose() {
    _nameController.dispose();
    _passwordController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      final repo = RepositoryProvider.of<AuthRepository>(context);
      await repo.register(
        name: _nameController.text.trim(),
        phone: widget.phone,
        password: _passwordController.text,
        code: _otpController.text.trim(),
      );

      if (!mounted) return;
      AppSnackbar.success(context, 'Muvaffaqiyatli ro\'yxatdan o\'tdingiz!');
      context.go('/home');
    } on DioException catch (e) {
      if (!mounted) return;
      AppSnackbar.error(context, e.message ?? 'Xatolik yuz berdi');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Ro\'yxatdan o\'tish')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Hisobingizni yarating', style: AppTextStyles.h3),
              const SizedBox(height: 8),
              Text(widget.phone, style: AppTextStyles.body2),
              const SizedBox(height: 32),

              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(
                  labelText: 'Ism va familiya',
                  prefixIcon: Icon(Icons.person_outline_rounded),
                ),
                validator: (v) =>
                    v == null || v.isEmpty ? 'Ism kiritish shart' : null,
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: _passwordController,
                obscureText: _obscurePassword,
                decoration: InputDecoration(
                  labelText: 'Parol',
                  prefixIcon: const Icon(Icons.lock_outline_rounded),
                  suffixIcon: IconButton(
                    icon: Icon(_obscurePassword
                        ? Icons.visibility_off_outlined
                        : Icons.visibility_outlined),
                    onPressed: () =>
                        setState(() => _obscurePassword = !_obscurePassword),
                  ),
                ),
                validator: (v) {
                  if (v == null || v.isEmpty) return 'Parol kiritish shart';
                  if (v.length < 6) return 'Kamida 6 ta belgi';
                  return null;
                },
              ),
              const SizedBox(height: 16),

              TextFormField(
                controller: _otpController,
                keyboardType: TextInputType.number,
                maxLength: 4,
                decoration: const InputDecoration(
                  labelText: 'SMS kod (4 xona)',
                  prefixIcon: Icon(Icons.sms_outlined),
                  counterText: '',
                ),
                validator: (v) =>
                    v?.length != 4 ? 'SMS kodni kiriting' : null,
              ),
              const SizedBox(height: 32),

              AppButton(
                label: 'Ro\'yxatdan o\'tish',
                isLoading: _isLoading,
                onPressed: _register,
                icon: Icons.check_rounded,
              ),

              const SizedBox(height: 24),
              Center(
                child: RichText(
                  textAlign: TextAlign.center,
                  text: TextSpan(
                    style: AppTextStyles.caption,
                    children: const [
                      TextSpan(text: 'Ro\'yxatdan o\'tish orqali siz '),
                      TextSpan(
                        text: 'Foydalanish shartlari',
                        style: TextStyle(color: AppTheme.primary),
                      ),
                      TextSpan(text: 'ga rozilik bildirasiz'),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
