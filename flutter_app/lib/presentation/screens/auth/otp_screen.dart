import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:pinput/pinput.dart';
import 'package:dio/dio.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/repositories/auth_repository.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/app_snackbar.dart';

class OtpScreen extends StatefulWidget {
  final String phone;
  final String type;

  const OtpScreen({super.key, required this.phone, required this.type});

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final _pinController = TextEditingController();
  final _focusNode = FocusNode();
  bool _isLoading = false;
  bool _isVerifying = false;
  int _countdown = 60;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  void _startCountdown() {
    _countdown = 60;
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_countdown <= 0) {
        timer.cancel();
      } else {
        setState(() => _countdown--);
      }
    });
  }

  @override
  void dispose() {
    _pinController.dispose();
    _focusNode.dispose();
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _verify(String code) async {
    if (code.length < 4) return;
    if (_isVerifying) return;

    setState(() => _isVerifying = true);

    try {
      final repo = RepositoryProvider.of<AuthRepository>(context);

      if (widget.type == 'login') {
        // Try OTP login first
        try {
          await repo.loginWithOtp(widget.phone, code);
          if (!mounted) return;
          context.go('/home');
          return;
        } catch (_) {
          // User might not exist, check OTP and go to register
        }
      }

      // Verify OTP only
      final result = await repo.verifyOtp(widget.phone, code, widget.type);
      if (!mounted) return;

      if (result['verified'] == true) {
        if (widget.type == 'register') {
          context.push('/auth/register', extra: {'phone': widget.phone});
        }
      }
    } on DioException catch (e) {
      if (!mounted) return;
      AppSnackbar.error(context, e.message ?? 'Kod noto\'g\'ri');
      _pinController.clear();
    } finally {
      if (mounted) setState(() => _isVerifying = false);
    }
  }

  Future<void> _resend() async {
    if (_countdown > 0) return;
    setState(() => _isLoading = true);
    try {
      final repo = RepositoryProvider.of<AuthRepository>(context);
      await repo.sendOtp(widget.phone, widget.type);
      if (!mounted) return;
      AppSnackbar.success(context, 'Kod qaytadan yuborildi');
      _startCountdown();
    } on DioException catch (e) {
      if (!mounted) return;
      AppSnackbar.error(context, e.message ?? 'Xatolik yuz berdi');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final defaultPinTheme = PinTheme(
      width: 64,
      height: 64,
      textStyle: const TextStyle(
        fontSize: 24,
        fontWeight: FontWeight.w700,
        color: AppTheme.textPrimary,
      ),
      decoration: BoxDecoration(
        color: AppTheme.background,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppTheme.border),
      ),
    );

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded),
          onPressed: () => context.pop(),
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(height: 20),
            const Text('📱', style: TextStyle(fontSize: 64)),
            const SizedBox(height: 24),
            Text('Kodni kiriting', style: AppTextStyles.h2),
            const SizedBox(height: 12),
            Text(
              '${widget.phone} raqamiga 4 xonali SMS kod yuborildi',
              style: AppTextStyles.body2,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 40),

            // PIN Input
            Pinput(
              length: 4,
              controller: _pinController,
              focusNode: _focusNode,
              autofocus: true,
              defaultPinTheme: defaultPinTheme,
              focusedPinTheme: defaultPinTheme.copyWith(
                decoration: defaultPinTheme.decoration!.copyWith(
                  border: Border.all(color: AppTheme.primary, width: 2),
                ),
              ),
              submittedPinTheme: defaultPinTheme.copyWith(
                decoration: defaultPinTheme.decoration!.copyWith(
                  color: AppTheme.primary.withOpacity(0.1),
                  border: Border.all(color: AppTheme.primary),
                ),
              ),
              errorPinTheme: defaultPinTheme.copyWith(
                decoration: defaultPinTheme.decoration!.copyWith(
                  border: Border.all(color: AppTheme.danger),
                ),
              ),
              onCompleted: _verify,
            ),

            const SizedBox(height: 32),

            // Loading indicator
            if (_isVerifying) ...[
              const CircularProgressIndicator(),
              const SizedBox(height: 16),
              Text('Tekshirilmoqda...', style: AppTextStyles.body2),
            ],

            const SizedBox(height: 24),

            // Resend
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text('Kodni olmadingizmi? ', style: AppTextStyles.body2),
                GestureDetector(
                  onTap: _countdown == 0 ? _resend : null,
                  child: Text(
                    _countdown > 0 ? '$_countdown s' : 'Qayta yuborish',
                    style: TextStyle(
                      color: _countdown == 0 ? AppTheme.primary : AppTheme.textHint,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
