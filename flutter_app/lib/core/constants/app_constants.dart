class AppConstants {
  // API
  static const String baseUrl = 'https://api.oynaa.uz/api/v1';
  static const String googleMapsApiKey = 'YOUR_GOOGLE_MAPS_API_KEY';

  // Storage keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String langKey = 'app_lang';
  static const String onboardingKey = 'onboarding_done';

  // Payme
  static const String paymeTestUrl = 'https://test.paycom.uz';
  static const String paymeUrl = 'https://checkout.paycom.uz';

  // Click
  static const String clickUrl = 'https://my.click.uz/services/pay';

  // Pagination
  static const int pageSize = 15;

  // Map
  static const double defaultLat = 41.2995;
  static const double defaultLng = 69.2401;
  static const double defaultZoom = 12.0;
  static const double defaultRadius = 10.0; // km

  // App
  static const String appName = "O'ynaa";
  static const String supportPhone = '+998712345678';
  static const String telegramChannel = 'https://t.me/oynaa_uz';
}
