import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/booking_model.dart';
import '../../../data/repositories/booking_repository.dart';
import '../../widgets/common/app_snackbar.dart';

class BookingDetailScreen extends StatefulWidget {
  final int bookingId;
  const BookingDetailScreen({super.key, required this.bookingId});

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  BookingModel? _booking;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadBooking();
  }

  Future<void> _loadBooking() async {
    try {
      final repo = RepositoryProvider.of<BookingRepository>(context);
      final b = await repo.getBookingDetail(widget.bookingId);
      if (mounted) setState(() { _booking = b; _isLoading = false; });
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _cancelBooking() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Bronni bekor qilish'),
        content: const Text('Bronni bekor qilmoqchimisiz?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Yo\'q')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger, minimumSize: const Size(0, 36)),
            child: const Text('Bekor qilish'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      try {
        final repo = RepositoryProvider.of<BookingRepository>(context);
        await repo.cancelBooking(widget.bookingId);
        if (!mounted) return;
        AppSnackbar.success(context, 'Bron bekor qilindi');
        context.pop();
      } catch (_) {
        if (!mounted) return;
        AppSnackbar.error(context, 'Xatolik yuz berdi');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    if (_booking == null) return Scaffold(appBar: AppBar(), body: const Center(child: Text('Topilmadi')));

    final b = _booking!;
    return Scaffold(
      appBar: AppBar(title: Text('#${b.bookingNumber}')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Status card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: b.isConfirmed ? [AppTheme.info, const Color(0xFF74B9FF)] :
                          b.isCompleted ? [AppTheme.success, const Color(0xFF00B894)] :
                          b.isCancelled ? [AppTheme.danger, const Color(0xFFE17055)] :
                          [AppTheme.warning, const Color(0xFFFDCB6E)],
                ),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Column(
                children: [
                  Text(b.statusLabel, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 4),
                  Text(b.bookingNumber, style: const TextStyle(color: Colors.white70, letterSpacing: 2)),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Details
            _DetailCard(children: [
              _DetailRow(label: 'Maydon', value: b.venue?.name ?? '—'),
              _DetailRow(label: 'Manzil', value: b.venue?.address ?? '—'),
              _DetailRow(label: 'Sana', value: b.bookingDate),
              _DetailRow(label: 'Vaqt', value: '${b.startTime} – ${b.endTime}'),
              _DetailRow(label: 'Davomiylik', value: '${b.durationHours} soat'),
            ]),
            const SizedBox(height: 12),

            _DetailCard(children: [
              _DetailRow(label: 'Narx/soat', value: '${NumberFormat("#,###").format(b.pricePerHour)} so\'m'),
              _DetailRow(label: 'Jami', value: '${NumberFormat("#,###").format(b.finalAmount)} so\'m', bold: true),
              _DetailRow(label: 'To\'lov holati', value: b.paymentStatusLabel),
              if (b.paymentMethod != null)
                _DetailRow(label: 'To\'lov usuli', value: b.paymentMethod!.toUpperCase()),
            ]),

            if (b.notes != null) ...[
              const SizedBox(height: 12),
              _DetailCard(children: [
                _DetailRow(label: 'Izoh', value: b.notes!),
              ]),
            ],

            const SizedBox(height: 24),

            // Actions
            if (b.canCancel)
              ElevatedButton.icon(
                onPressed: _cancelBooking,
                icon: const Icon(Icons.cancel_outlined),
                label: const Text('Bronni bekor qilish'),
                style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger),
              ),

            if (b.isPaid && !b.isPending)
              Padding(
                padding: const EdgeInsets.only(top: 12),
                child: OutlinedButton.icon(
                  onPressed: () => context.push('/venues/${b.venue?.id}'),
                  icon: const Icon(Icons.location_on_outlined),
                  label: const Text('Xaritada ko\'rish'),
                ),
              ),

            if (b.canReview)
              Padding(
                padding: const EdgeInsets.only(top: 12),
                child: ElevatedButton.icon(
                  onPressed: () {/* show review dialog */},
                  icon: const Icon(Icons.star_outline_rounded),
                  label: const Text('Izoh qoldirish'),
                  style: ElevatedButton.styleFrom(backgroundColor: AppTheme.warning),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _DetailCard extends StatelessWidget {
  final List<Widget> children;
  const _DetailCard({required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(children: children),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool bold;

  const _DetailRow({required this.label, required this.value, this.bold = false});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 120, child: Text(label, style: AppTextStyles.body2)),
          Expanded(
            child: Text(
              value,
              style: bold
                  ? AppTextStyles.body1.copyWith(fontWeight: FontWeight.w700, color: AppTheme.primary)
                  : AppTextStyles.body1,
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }
}
