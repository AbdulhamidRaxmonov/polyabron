import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/repositories/booking_repository.dart';
import '../../widgets/common/app_snackbar.dart';

enum PaymentMethod { payme, click, cash }

class PaymentScreen extends StatefulWidget {
  final int bookingId;
  final double amount;

  const PaymentScreen({super.key, required this.bookingId, required this.amount});

  @override
  State<PaymentScreen> createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  PaymentMethod _selected = PaymentMethod.payme;
  bool _isLoading = false;

  Future<void> _processPayment() async {
    setState(() => _isLoading = true);

    try {
      final repo = RepositoryProvider.of<BookingRepository>(context);

      if (_selected == PaymentMethod.cash) {
        // Cash payment — just confirm booking
        if (!mounted) return;
        context.pushReplacement('/payment/success', extra: {
          'booking_number': 'BK${widget.bookingId}',
        });
        return;
      }

      String paymentUrl;
      if (_selected == PaymentMethod.payme) {
        paymentUrl = await repo.getPaymeUrl(widget.bookingId);
      } else {
        paymentUrl = await repo.getClickUrl(widget.bookingId);
      }

      final uri = Uri.parse(paymentUrl);
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
        // After returning from payment app
        if (!mounted) return;
        _showPaymentResult();
      } else {
        throw Exception('To\'lov ilovasini ochib bo\'lmadi');
      }
    } catch (e) {
      if (!mounted) return;
      AppSnackbar.error(context, e.toString());
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showPaymentResult() {
    showModalBottomSheet(
      context: context,
      isDismissible: false,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text("To'lov qanday bo'ldi?", style: AppTextStyles.h4),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      Navigator.pop(ctx);
                      context.go('/bookings');
                    },
                    icon: const Icon(Icons.close_rounded),
                    label: const Text('Bekor qilindi'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppTheme.danger,
                      side: const BorderSide(color: AppTheme.danger),
                      minimumSize: const Size(0, 48),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.pop(ctx);
                      context.pushReplacement('/payment/success', extra: {
                        'booking_number': 'BK${widget.bookingId}',
                      });
                    },
                    icon: const Icon(Icons.check_rounded),
                    label: const Text("To'landi"),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppTheme.success,
                      minimumSize: const Size(0, 48),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("To'lov")),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Amount Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [AppTheme.primary, AppTheme.primaryLight],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                children: [
                  const Text("To'lov summasi",
                      style: TextStyle(color: Colors.white70, fontSize: 14)),
                  const SizedBox(height: 8),
                  Text(
                    '${NumberFormat('#,###').format(widget.amount)} so\'m',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text('Bron #${widget.bookingId}',
                      style: const TextStyle(color: Colors.white60, fontSize: 13)),
                ],
              ),
            ),
            const SizedBox(height: 28),

            Text("To'lov usuli", style: AppTextStyles.h5),
            const SizedBox(height: 12),

            // Payment methods
            ...[
              _PaymentMethodTile(
                method: PaymentMethod.payme,
                logo: '💳',
                name: 'Payme',
                description: 'Tezkor va xavfsiz to\'lov',
                selected: _selected,
                onSelect: (m) => setState(() => _selected = m),
              ),
              _PaymentMethodTile(
                method: PaymentMethod.click,
                logo: '🔵',
                name: 'Click',
                description: 'Click hamyoni orqali to\'lov',
                selected: _selected,
                onSelect: (m) => setState(() => _selected = m),
              ),
              _PaymentMethodTile(
                method: PaymentMethod.cash,
                logo: '💵',
                name: 'Naqd pul',
                description: 'Maydon egasiga naqd to\'lang',
                selected: _selected,
                onSelect: (m) => setState(() => _selected = m),
              ),
            ],

            const Spacer(),

            // Pay button
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton.icon(
                onPressed: _isLoading ? null : _processPayment,
                icon: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : Icon(_selected == PaymentMethod.cash
                        ? Icons.check_rounded
                        : Icons.open_in_new_rounded),
                label: Text(
                  _selected == PaymentMethod.cash
                      ? 'Tasdiqlash'
                      : _selected == PaymentMethod.payme
                          ? 'Payme orqali to\'lash'
                          : 'Click orqali to\'lash',
                ),
              ),
            ),
            const SizedBox(height: 8),
            Center(
              child: Text(
                '🔒 To\'lovingiz himoyalangan',
                style: AppTextStyles.caption,
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}

class _PaymentMethodTile extends StatelessWidget {
  final PaymentMethod method;
  final String logo;
  final String name;
  final String description;
  final PaymentMethod selected;
  final ValueChanged<PaymentMethod> onSelect;

  const _PaymentMethodTile({
    required this.method,
    required this.logo,
    required this.name,
    required this.description,
    required this.selected,
    required this.onSelect,
  });

  bool get isSelected => selected == method;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => onSelect(method),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primary.withOpacity(0.05) : Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? AppTheme.primary : AppTheme.border,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            Text(logo, style: const TextStyle(fontSize: 28)),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600)),
                  Text(description, style: AppTextStyles.caption),
                ],
              ),
            ),
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              width: 22,
              height: 22,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: isSelected ? AppTheme.primary : Colors.transparent,
                border: Border.all(
                  color: isSelected ? AppTheme.primary : AppTheme.border,
                  width: 2,
                ),
              ),
              child: isSelected
                  ? const Icon(Icons.check_rounded, color: Colors.white, size: 14)
                  : null,
            ),
          ],
        ),
      ),
    );
  }
}
