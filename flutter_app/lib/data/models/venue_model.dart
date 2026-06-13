class CategoryModel {
  final int id;
  final String name;
  final String? icon;

  const CategoryModel({required this.id, required this.name, this.icon});

  factory CategoryModel.fromJson(Map<String, dynamic> json) => CategoryModel(
        id: json['id'],
        name: json['name'] ?? json['name_uz'] ?? '',
        icon: json['icon'],
      );
}

class VenueModel {
  final int id;
  final String name;
  final String? description;
  final String address;
  final double latitude;
  final double longitude;
  final String city;
  final String? phone;
  final double pricePerHour;
  final double? priceWeekend;
  final String openTime;
  final String closeTime;
  final double rating;
  final int reviewsCount;
  final int bookingsCount;
  final bool isFeatured;
  final List<String> amenities;
  final String? coverImage;
  final List<String> images;
  final CategoryModel? category;
  final double? distance;
  final bool? isFavorite;

  const VenueModel({
    required this.id,
    required this.name,
    this.description,
    required this.address,
    required this.latitude,
    required this.longitude,
    required this.city,
    this.phone,
    required this.pricePerHour,
    this.priceWeekend,
    required this.openTime,
    required this.closeTime,
    required this.rating,
    required this.reviewsCount,
    required this.bookingsCount,
    required this.isFeatured,
    required this.amenities,
    this.coverImage,
    required this.images,
    this.category,
    this.distance,
    this.isFavorite,
  });

  factory VenueModel.fromJson(Map<String, dynamic> json) => VenueModel(
        id: json['id'],
        name: json['name'] ?? '',
        description: json['description'],
        address: json['address'] ?? '',
        latitude: (json['latitude'] ?? 0).toDouble(),
        longitude: (json['longitude'] ?? 0).toDouble(),
        city: json['city'] ?? '',
        phone: json['phone'],
        pricePerHour: (json['price_per_hour'] ?? 0).toDouble(),
        priceWeekend: json['price_weekend'] != null
            ? (json['price_weekend']).toDouble()
            : null,
        openTime: json['open_time'] ?? '08:00',
        closeTime: json['close_time'] ?? '23:00',
        rating: (json['rating'] ?? 0).toDouble(),
        reviewsCount: json['reviews_count'] ?? 0,
        bookingsCount: json['bookings_count'] ?? 0,
        isFeatured: json['is_featured'] ?? false,
        amenities: json['amenities'] != null
            ? List<String>.from(json['amenities'])
            : [],
        coverImage: json['cover_image'],
        images: json['images'] != null ? List<String>.from(json['images']) : [],
        category: json['category'] != null
            ? CategoryModel.fromJson(json['category'])
            : null,
        distance: json['distance']?.toDouble(),
        isFavorite: json['is_favorite'],
      );

  String get formattedPrice =>
      '${(pricePerHour / 1000).toStringAsFixed(0)}K so\'m/soat';

  String get formattedDistance =>
      distance != null ? '${distance!.toStringAsFixed(1)} km' : '';
}
