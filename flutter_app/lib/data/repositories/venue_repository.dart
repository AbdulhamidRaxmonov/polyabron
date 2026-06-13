import '../models/venue_model.dart';
import '../models/booking_model.dart';
import '../../core/network/api_client.dart';

class VenueRepository {
  final ApiClient _api;

  VenueRepository(this._api);

  Future<Map<String, dynamic>> getHomeData({double? lat, double? lng}) async {
    final params = <String, dynamic>{};
    if (lat != null) params['lat'] = lat;
    if (lng != null) params['lng'] = lng;

    final response = await _api.get('/home', params: params);
    return response.data['data'];
  }

  Future<List<CategoryModel>> getCategories() async {
    final response = await _api.get('/categories');
    final list = response.data['data'] as List;
    return list.map((e) => CategoryModel.fromJson(e)).toList();
  }

  Future<Map<String, dynamic>> getVenues({
    int? categoryId,
    String? search,
    double? minPrice,
    double? maxPrice,
    double? lat,
    double? lng,
    double? radius,
    String? sortBy,
    int page = 1,
  }) async {
    final params = <String, dynamic>{'page': page};
    if (categoryId != null) params['category_id'] = categoryId;
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (minPrice != null) params['min_price'] = minPrice;
    if (maxPrice != null) params['max_price'] = maxPrice;
    if (lat != null) params['lat'] = lat;
    if (lng != null) params['lng'] = lng;
    if (radius != null) params['radius'] = radius;
    if (sortBy != null) params['sort_by'] = sortBy;

    final response = await _api.get('/venues', params: params);
    final data = response.data['data'] as List;
    final meta = response.data['meta'];

    return {
      'venues': data.map((e) => VenueModel.fromJson(e)).toList(),
      'meta': meta,
    };
  }

  Future<List<VenueModel>> getNearbyVenues(double lat, double lng,
      {double radius = 10}) async {
    final response = await _api.get('/venues/nearby', params: {
      'lat': lat,
      'lng': lng,
      'radius': radius,
    });
    final list = response.data['data'] as List;
    return list.map((e) => VenueModel.fromJson(e)).toList();
  }

  Future<VenueModel> getVenueDetail(int id) async {
    final response = await _api.get('/venues/$id');
    return VenueModel.fromJson(response.data['data']);
  }

  Future<List<TimeSlotModel>> getAvailability(int venueId, String date) async {
    final response = await _api.get(
      '/venues/$venueId/availability',
      params: {'date': date},
    );
    final list = response.data['slots'] as List;
    return list.map((e) => TimeSlotModel.fromJson(e)).toList();
  }

  Future<Map<String, dynamic>> getVenueReviews(int venueId,
      {int page = 1}) async {
    final response = await _api.get(
      '/venues/$venueId/reviews',
      params: {'page': page},
    );
    return response.data;
  }

  Future<bool> toggleFavorite(int venueId) async {
    final response = await _api.post('/profile/favorites/$venueId');
    return response.data['is_favorite'] ?? false;
  }

  Future<List<VenueModel>> getFavorites() async {
    final response = await _api.get('/profile/favorites');
    final list = response.data['data'] as List;
    return list.map((e) => VenueModel.fromJson(e)).toList();
  }
}
