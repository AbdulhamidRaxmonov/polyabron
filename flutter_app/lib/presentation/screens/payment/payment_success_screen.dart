import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

class PaymentSuccessScreen extends StatelessWidget {
  final String bookingNumber;
  const PaymentSuccessScreen({super.key, required this.bookingNumber});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Success animation
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: AppTheme.success.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.check_circle_rounded,
                    color: AppTheme.success, size: 72),
              ),
              const SizedBox(height: 32),

              Text("To'lov muvaffaqiyatli!", style: AppTextStyles.h2.copyWith(color: AppTheme.success)),
              const SizedBox(height: 12),
              Text(
                'Broningiz tasdiqlandi. Maydonda ko\'rishguncha!',
                style: AppTextStyles.body2,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),

              // Booking number
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                decoration: BoxDecoration(
                  color: AppTheme.background,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Column(
                  children: [
                    Text('Bron raqami', style: AppTextStyles.caption),
                    const SizedBox(height: 4),
                    Text(bookingNumber,
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 2,
                          color: AppTheme.primary,
                        )),
                  ],
                ),
              ),
              const SizedBox(height: 48),

              ElevatedButton(
                onPressed: () => context.go('/bookings'),
                child: const Text('Bronlarimni ko\'rish'),
              ),
              const SizedBox(height: 12),

              TextButton(
                onPressed: () => context.go('/home'),
                child: const Text('Bosh sahifaga qaytish'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
