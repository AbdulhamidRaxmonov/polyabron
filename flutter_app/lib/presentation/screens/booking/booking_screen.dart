import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/booking_model.dart';
import '../../../data/repositories/booking_repository.dart';
import '../../../data/repositories/venue_repository.dart';
import '../../widgets/common/app_button.dart';
import '../../widgets/common/app_snackbar.dart';

class BookingScreen extends StatefulWidget {
  final int venueId;
  const BookingScreen({super.key, required this.venueId});

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  DateTime _selectedDate = DateTime.now().add(const Duration(days: 1));
  List<TimeSlotModel> _slots = [];
  List<int> _selectedSlotIndexes = [];
  bool _isLoadingSlots = false;
  bool _isBooking = false;
  final _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadSlots();
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadSlots() async {
    setState(() { _isLoadingSlots = true; _selectedSlotIndexes = []; });
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final slots = await repo.getAvailability(
        widget.venueId,
        DateFormat('yyyy-MM-dd').format(_selectedDate),
      );
      if (mounted) setState(() { _slots = slots; _isLoadingSlots = false; });
    } catch (e) {
      if (mounted) setState(() => _isLoadingSlots = false);
      if (mounted) AppSnackbar.error(context, 'Vaqtlarni yuklashda xatolik');
    }
  }

  void _toggleSlot(int index) {
    if (_slots[index].isBooked) return;
    setState(() {
      if (_selectedSlotIndexes.contains(index)) {
        _selectedSlotIndexes.remove(index);
      } else {
        // Only allow consecutive slots
        if (_selectedSlotIndexes.isEmpty) {
          _selectedSlotIndexes.add(index);
        } else {
          final sorted = List<int>.from(_selectedSlotIndexes)..sort();
          final min = sorted.first;
          final max = sorted.last;
          if (index == min - 1 || index == max + 1) {
            _selectedSlotIndexes.add(index);
          } else {
            _selectedSlotIndexes = [index];
          }
        }
      }
    });
  }

  double get _totalAmount {
    if (_selectedSlotIndexes.isEmpty) return 0;
    final hourlyPrice = _slots.first.price;
    return hourlyPrice * _selectedSlotIndexes.length;
  }

  Future<void> _book() async {
    if (_selectedSlotIndexes.isEmpty) {
      AppSnackbar.error(context, 'Kamida bitta vaqt tanlang');
      return;
    }

    setState(() => _isBooking = true);

    try {
      final sorted = List<int>.from(_selectedSlotIndexes)..sort();
      final startSlot = _slots[sorted.first];
      final endSlot = _slots[sorted.last];

      final repo = RepositoryProvider.of<BookingRepository>(context);
      final booking = await repo.createBooking(
        venueId: widget.venueId,
        bookingDate: DateFormat('yyyy-MM-dd').format(_selectedDate),
        startTime: startSlot.startTime.substring(0, 5),
        endTime: endSlot.endTime.substring(0, 5),
        notes: _notesController.text.isNotEmpty ? _notesController.text : null,
      );

      if (!mounted) return;

      // Go to payment screen
      context.pushReplacement('/payment', extra: {
        'booking_id': booking.id,
        'amount': booking.finalAmount,
        'booking_number': booking.bookingNumber,
      });
    } catch (e) {
      if (!mounted) return;
      AppSnackbar.error(context, 'Bron qilishda xatolik yuz berdi');
    } finally {
      if (mounted) setState(() => _isBooking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Bron qilish')),
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Date picker
                  Text('Sana tanlang', style: AppTextStyles.h5),
                  const SizedBox(height: 12),
                  _DateSelector(
                    selectedDate: _selectedDate,
                    onDateChanged: (date) {
                      setState(() => _selectedDate = date);
                      _loadSlots();
                    },
                  ),
                  const SizedBox(height: 24),

                  // Time slots
                  Row(
                    children: [
                      Text('Vaqt tanlang', style: AppTextStyles.h5),
                      const Spacer(),
                      if (_selectedSlotIndexes.isNotEmpty)
                        Text(
                          '${_selectedSlotIndexes.length} soat',
                          style: const TextStyle(color: AppTheme.primary, fontWeight: FontWeight.w600),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text('Ketma-ket vaqtlarni tanlashingiz mumkin', style: AppTextStyles.caption),
                  const SizedBox(height: 12),

                  if (_isLoadingSlots)
                    const Center(child: Padding(
                      padding: EdgeInsets.all(32),
                      child: CircularProgressIndicator(),
                    ))
                  else if (_slots.isEmpty)
                    const _EmptySlots()
                  else
                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 3,
                        mainAxisSpacing: 8,
                        crossAxisSpacing: 8,
                        childAspectRatio: 2.4,
                      ),
                      itemCount: _slots.length,
                      itemBuilder: (_, i) {
                        final slot = _slots[i];
                        final isSelected = _selectedSlotIndexes.contains(i);
                        return _TimeSlotChip(
                          slot: slot,
                          isSelected: isSelected,
                          onTap: () => _toggleSlot(i),
                        );
                      },
                    ),

                  const SizedBox(height: 20),

                  // Legend
                  Row(
                    children: [
                      _Legend(color: AppTheme.primary.withOpacity(0.15), borderColor: AppTheme.primary, label: 'Tanlangan'),
                      const SizedBox(width: 16),
                      _Legend(color: const Color(0xFFF8D7DA), borderColor: AppTheme.danger, label: 'Band'),
                      const SizedBox(width: 16),
                      _Legend(color: AppTheme.background, borderColor: AppTheme.border, label: 'Bo\'sh'),
                    ],
                  ),

                  const SizedBox(height: 20),

                  // Notes
                  Text('Izoh (ixtiyoriy)', style: AppTextStyles.h5),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _notesController,
                    maxLines: 2,
                    maxLength: 500,
                    decoration: const InputDecoration(
                      hintText: 'Qo\'shimcha ma\'lumot...',
                    ),
                  ),
                ],
              ),
            ),
          ),

          // Bottom summary
          if (_selectedSlotIndexes.isNotEmpty)
            Container(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 16, offset: const Offset(0, -4))],
              ),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Jami summa:', style: AppTextStyles.body2),
                      Text(
                        '${NumberFormat('#,###').format(_totalAmount)} so\'m',
                        style: AppTextStyles.price,
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  AppButton(
                    label: 'To\'lovga o\'tish',
                    isLoading: _isBooking,
                    onPressed: _book,
                    icon: Icons.payment_rounded,
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _DateSelector extends StatelessWidget {
  final DateTime selectedDate;
  final ValueChanged<DateTime> onDateChanged;

  const _DateSelector({required this.selectedDate, required this.onDateChanged});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 76,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: 14,
        itemBuilder: (_, i) {
          final date = DateTime.now().add(Duration(days: i + 1));
          final isSelected = DateFormat('yyyy-MM-dd').format(date) ==
              DateFormat('yyyy-MM-dd').format(selectedDate);
          final isWeekend = date.weekday == DateTime.saturday || date.weekday == DateTime.sunday;

          return GestureDetector(
            onTap: () => onDateChanged(date),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: const EdgeInsets.only(right: 10),
              width: 56,
              decoration: BoxDecoration(
                color: isSelected ? AppTheme.primary : AppTheme.background,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: isSelected ? AppTheme.primary : AppTheme.border,
                ),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    DateFormat('EEE', 'uz').format(date).toUpperCase(),
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: isSelected ? Colors.white70 : AppTheme.textHint,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    DateFormat('d').format(date),
                    style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: isSelected ? Colors.white : (isWeekend ? AppTheme.danger : AppTheme.textPrimary),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _TimeSlotChip extends StatelessWidget {
  final TimeSlotModel slot;
  final bool isSelected;
  final VoidCallback onTap;

  const _TimeSlotChip({required this.slot, required this.isSelected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: slot.isBooked ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        decoration: BoxDecoration(
          color: slot.isBooked
              ? const Color(0xFFF8D7DA)
              : isSelected
                  ? AppTheme.primary.withOpacity(0.15)
                  : AppTheme.background,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: slot.isBooked
                ? AppTheme.danger.withOpacity(0.3)
                : isSelected
                    ? AppTheme.primary
                    : AppTheme.border,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Center(
          child: Text(
            slot.label,
            style: TextStyle(
              fontSize: 11,
              fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
              color: slot.isBooked
                  ? AppTheme.danger
                  : isSelected
                      ? AppTheme.primary
                      : AppTheme.textSecondary,
            ),
          ),
        ),
      ),
    );
  }
}

class _Legend extends StatelessWidget {
  final Color color;
  final Color borderColor;
  final String label;

  const _Legend({required this.color, required this.borderColor, required this.label});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 14,
          height: 14,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(4),
            border: Border.all(color: borderColor),
          ),
        ),
        const SizedBox(width: 5),
        Text(label, style: AppTextStyles.caption),
      ],
    );
  }
}

class _EmptySlots extends StatelessWidget {
  const _EmptySlots();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(32),
        child: Column(
          children: [
            Text('😔', style: TextStyle(fontSize: 48)),
            SizedBox(height: 8),
            Text('Ushbu kunga bo\'sh vaqt yo\'q', style: AppTextStyles.body2),
          ],
        ),
      ),
    );
  }
}
