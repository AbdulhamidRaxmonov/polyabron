import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/booking_model.dart';
import '../../../data/repositories/booking_repository.dart';

class BookingListScreen extends StatefulWidget {
  const BookingListScreen({super.key});

  @override
  State<BookingListScreen> createState() => _BookingListScreenState();
}

class _BookingListScreenState extends State<BookingListScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final Map<String, List<BookingModel>> _bookings = {
    'upcoming': [],
    'completed': [],
    'cancelled': [],
  };
  final Map<String, bool> _loading = {
    'upcoming': true,
    'completed': false,
    'cancelled': false,
  };
  String _currentTab = 'upcoming';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        final tab = ['upcoming', 'completed', 'cancelled'][_tabController.index];
        if (_bookings[tab]!.isEmpty) {
          setState(() => _currentTab = tab);
          _loadBookings(tab);
        } else {
          setState(() => _currentTab = tab);
        }
      }
    });
    _loadBookings('upcoming');
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadBookings(String tab) async {
    setState(() => _loading[tab] = true);
    try {
      final repo = RepositoryProvider.of<BookingRepository>(context);
      String? status;
      if (tab == 'upcoming') status = 'confirmed';
      if (tab == 'completed') status = 'completed';
      if (tab == 'cancelled') status = 'cancelled';

      final bookings = await repo.getBookings(status: status);
      if (mounted) setState(() { _bookings[tab] = bookings; _loading[tab] = false; });
    } catch (_) {
      if (mounted) setState(() => _loading[tab] = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Bronlarim'),
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppTheme.primary,
          unselectedLabelColor: AppTheme.textHint,
          indicatorColor: AppTheme.primary,
          indicatorWeight: 3,
          tabs: const [
            Tab(text: 'Kelayotgan'),
            Tab(text: 'Yakunlangan'),
            Tab(text: 'Bekor qilingan'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: ['upcoming', 'completed', 'cancelled'].map((tab) {
          if (_loading[tab] == true) {
            return const Center(child: CircularProgressIndicator());
          }
          final bookings = _bookings[tab]!;
          if (bookings.isEmpty) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('📭', style: const TextStyle(fontSize: 64)),
                  const SizedBox(height: 16),
                  Text('Hali bronlar yo\'q', style: AppTextStyles.h5),
                  const SizedBox(height: 8),
                  Text('Sport maydonlarini qidiring va bron qiling!', style: AppTextStyles.body2),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () => context.go('/venues'),
                    style: ElevatedButton.styleFrom(minimumSize: const Size(0, 44)),
                    child: const Text('Maydonlarni ko\'rish'),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => _loadBookings(tab),
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: bookings.length,
              itemBuilder: (_, i) => _BookingCard(
                booking: bookings[i],
                onTap: () => context.push('/bookings/${bookings[i].id}'),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _BookingCard extends StatelessWidget {
  final BookingModel booking;
  final VoidCallback onTap;

  const _BookingCard({required this.booking, required this.onTap});

  Color get _statusColor {
    switch (booking.status) {
      case 'confirmed': return AppTheme.info;
      case 'completed': return AppTheme.success;
      case 'cancelled': return AppTheme.danger;
      default: return AppTheme.warning;
    }
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.border),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    booking.venue?.name ?? '—',
                    style: AppTextStyles.h5,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: _statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    booking.statusLabel,
                    style: TextStyle(color: _statusColor, fontSize: 11, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                _InfoBadge(icon: Icons.calendar_today_outlined, text: booking.bookingDate),
                const SizedBox(width: 12),
                _InfoBadge(
                  icon: Icons.access_time_outlined,
                  text: '${booking.startTime} – ${booking.endTime}',
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                _InfoBadge(icon: Icons.timer_outlined, text: '${booking.durationHours} soat'),
                const Spacer(),
                Text(
                  '${NumberFormat('#,###').format(booking.finalAmount)} so\'m',
                  style: AppTextStyles.price,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Text('#${booking.bookingNumber}', style: AppTextStyles.caption),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: booking.isPaid
                        ? AppTheme.success.withOpacity(0.1)
                        : AppTheme.warning.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    booking.paymentStatusLabel,
                    style: TextStyle(
                      color: booking.isPaid ? AppTheme.success : AppTheme.warning,
                      fontSize: 11,
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

class _InfoBadge extends StatelessWidget {
  final IconData icon;
  final String text;

  const _InfoBadge({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 14, color: AppTheme.textHint),
        const SizedBox(width: 4),
        Text(text, style: AppTextStyles.caption.copyWith(color: AppTheme.textSecondary)),
      ],
    );
  }
}
