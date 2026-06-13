import 'venue_model.dart';

class BookingModel {
  final int id;
  final String bookingNumber;
  final String bookingDate;
  final String startTime;
  final String endTime;
  final int durationHours;
  final double pricePerHour;
  final double totalAmount;
  final double finalAmount;
  final String status;
  final String paymentStatus;
  final String? paymentMethod;
  final String? notes;
  final String? cancelReason;
  final String createdAt;
  final VenueModel? venue;

  const BookingModel({
    required this.id,
    required this.bookingNumber,
    required this.bookingDate,
    required this.startTime,
    required this.endTime,
    required this.durationHours,
    required this.pricePerHour,
    required this.totalAmount,
    required this.finalAmount,
    required this.status,
    required this.paymentStatus,
    this.paymentMethod,
    this.notes,
    this.cancelReason,
    required this.createdAt,
    this.venue,
  });

  factory BookingModel.fromJson(Map<String, dynamic> json) => BookingModel(
        id: json['id'],
        bookingNumber: json['booking_number'] ?? '',
        bookingDate: json['booking_date'] ?? '',
        startTime: json['start_time'] ?? '',
        endTime: json['end_time'] ?? '',
        durationHours: json['duration_hours'] ?? 1,
        pricePerHour: (json['price_per_hour'] ?? 0).toDouble(),
        totalAmount: (json['total_amount'] ?? 0).toDouble(),
        finalAmount: (json['final_amount'] ?? 0).toDouble(),
        status: json['status'] ?? 'pending',
        paymentStatus: json['payment_status'] ?? 'unpaid',
        paymentMethod: json['payment_method'],
        notes: json['notes'],
        cancelReason: json['cancel_reason'],
        createdAt: json['created_at'] ?? '',
        venue: json['venue'] != null ? VenueModel.fromJson(json['venue']) : null,
      );

  bool get isPending => status == 'pending';
  bool get isConfirmed => status == 'confirmed';
  bool get isCompleted => status == 'completed';
  bool get isCancelled => status == 'cancelled';
  bool get isPaid => paymentStatus == 'paid';
  bool get canCancel => status == 'pending' || status == 'confirmed';
  bool get canReview => status == 'completed' && isPaid;

  String get statusLabel {
    switch (status) {
      case 'pending': return 'Kutmoqda';
      case 'confirmed': return 'Tasdiqlangan';
      case 'completed': return 'Yakunlangan';
      case 'cancelled': return 'Bekor qilingan';
      case 'no_show': return 'Kelmagan';
      default: return status;
    }
  }

  String get paymentStatusLabel {
    switch (paymentStatus) {
      case 'unpaid': return 'To\'lanmagan';
      case 'paid': return 'To\'langan';
      case 'refunded': return 'Qaytarilgan';
      default: return paymentStatus;
    }
  }
}

class TimeSlotModel {
  final String startTime;
  final String endTime;
  final bool isBooked;
  final double price;

  const TimeSlotModel({
    required this.startTime,
    required this.endTime,
    required this.isBooked,
    required this.price,
  });

  factory TimeSlotModel.fromJson(Map<String, dynamic> json) => TimeSlotModel(
        startTime: json['start_time'] ?? '',
        endTime: json['end_time'] ?? '',
        isBooked: json['is_booked'] ?? false,
        price: (json['price'] ?? 0).toDouble(),
      );

  bool get isAvailable => !isBooked;

  String get label => '${startTime.substring(0, 5)} - ${endTime.substring(0, 5)}';
}
