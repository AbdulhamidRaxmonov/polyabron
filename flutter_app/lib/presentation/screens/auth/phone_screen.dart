import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:dio/dio.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/repositories/auth_repository.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/app_snackbar.dart';

class PhoneScreen extends StatefulWidget {
  const PhoneScreen({super.key});

  @override
  State<PhoneScreen> createState() => _PhoneScreenState();
}

class _PhoneScreenState extends State<PhoneScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController(text: '+998');
  bool _isLoading = false;
  String _selectedType = 'login';

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _sendOtp() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      final repo = RepositoryProvider.of<AuthRepository>(context);
      await repo.sendOtp(_phoneController.text.trim(), _selectedType);

      if (!mounted) return;
      context.push('/auth/otp', extra: {
        'phone': _phoneController.text.trim(),
        'type': _selectedType,
      });
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
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 40),

                // Logo
                Center(
                  child: Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [AppTheme.primary, AppTheme.primaryLight],
                      ),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Center(
                      child: Text("O'",
                          style: TextStyle(
                              fontSize: 28,
                              fontWeight: FontWeight.w900,
                              color: Colors.white)),
                    ),
                  ),
                ),
                const SizedBox(height: 40),

                Text('Telefon raqam', style: AppTextStyles.h3),
                const SizedBox(height: 8),
                Text(
                  'Kirish yoki ro\'yxatdan o\'tish uchun telefon raqamingizni kiriting',
                  style: AppTextStyles.body2,
                ),
                const SizedBox(height: 32),

                // Phone input
                TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [
                    FilteringTextInputFormatter.allow(RegExp(r'[\d+]')),
                    LengthLimitingTextInputFormatter(13),
                  ],
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, letterSpacing: 2),
                  decoration: InputDecoration(
                    prefixIcon: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Text('🇺🇿', style: TextStyle(fontSize: 22)),
                    ),
                    hintText: '+998 XX XXX XX XX',
                    labelText: 'Telefon raqam',
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'Telefon raqam kiritish shart';
                    if (!RegExp(r'^\+998\d{9}$').hasMatch(v.trim())) {
                      return 'To\'g\'ri format: +998901234567';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 24),

                // Mode selector
                Container(
                  decoration: BoxDecoration(
                    color: AppTheme.background,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: _ModeTab(
                          label: 'Kirish',
                          isSelected: _selectedType == 'login',
                          onTap: () => setState(() => _selectedType = 'login'),
                        ),
                      ),
                      Expanded(
                        child: _ModeTab(
                          label: 'Ro\'yxat',
                          isSelected: _selectedType == 'register',
                          onTap: () => setState(() => _selectedType = 'register'),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 32),

                AppButton(
                  label: 'Kodni yuborish',
                  isLoading: _isLoading,
                  onPressed: _sendOtp,
                  icon: Icons.arrow_forward_rounded,
                ),
                const SizedBox(height: 24),

                Center(
                  child: Text(
                    'Kodingiz SMS orqali yuboriladi',
                    style: AppTextStyles.caption,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ModeTab extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _ModeTab({required this.label, required this.isSelected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.all(4),
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Center(
          child: Text(
            label,
            style: TextStyle(
              color: isSelected ? Colors.white : AppTheme.textSecondary,
              fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
              fontSize: 14,
            ),
          ),
        ),
      ),
    );
  }
}
