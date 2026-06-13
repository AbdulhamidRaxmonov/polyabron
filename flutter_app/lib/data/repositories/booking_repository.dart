import '../models/booking_model.dart';
import '../../core/network/api_client.dart';

class BookingRepository {
  final ApiClient _api;

  BookingRepository(this._api);

  Future<List<BookingModel>> getBookings({String? status}) async {
    final params = <String, dynamic>{};
    if (status != null) params['status'] = status;
    final response = await _api.get('/bookings', params: params);
    final list = response.data['data'] as List;
    return list.map((e) => BookingModel.fromJson(e)).toList();
  }

  Future<BookingModel> getBookingDetail(int id) async {
    final response = await _api.get('/bookings/$id');
    return BookingModel.fromJson(response.data['data']);
  }

  Future<BookingModel> createBooking({
    required int venueId,
    required String bookingDate,
    required String startTime,
    required String endTime,
    String? notes,
  }) async {
    final response = await _api.post('/bookings', data: {
      'venue_id': venueId,
      'booking_date': bookingDate,
      'start_time': startTime,
      'end_time': endTime,
      if (notes != null) 'notes': notes,
    });
    return BookingModel.fromJson(response.data['data']);
  }

  Future<void> cancelBooking(int id, {String? reason}) async {
    await _api.post('/bookings/$id/cancel', data: {
      if (reason != null) 'reason': reason,
    });
  }

  // Payment URLs
  Future<String> getPaymeUrl(int bookingId) async {
    final response = await _api.get('/payment/payme/url/$bookingId');
    return response.data['payment_url'];
  }

  Future<String> getClickUrl(int bookingId) async {
    final response = await _api.get('/payment/click/url/$bookingId');
    return response.data['payment_url'];
  }

  Future<void> submitReview({
    required int bookingId,
    required int rating,
    String? comment,
  }) async {
    await _api.post('/reviews', data: {
      'booking_id': bookingId,
      'rating': rating,
      if (comment != null) 'comment': comment,
    });
  }
}
