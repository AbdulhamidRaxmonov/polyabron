import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../data/repositories/auth_repository.dart';
import '../screens/splash_screen.dart';
import '../screens/auth/onboarding_screen.dart';
import '../screens/auth/phone_screen.dart';
import '../screens/auth/otp_screen.dart';
import '../screens/auth/register_screen.dart';
import '../screens/home/main_shell.dart';
import '../screens/home/home_screen.dart';
import '../screens/venues/venues_list_screen.dart';
import '../screens/venues/venue_detail_screen.dart';
import '../screens/venues/venue_map_screen.dart';
import '../screens/booking/booking_screen.dart';
import '../screens/booking/booking_list_screen.dart';
import '../screens/booking/booking_detail_screen.dart';
import '../screens/payment/payment_screen.dart';
import '../screens/payment/payment_success_screen.dart';
import '../screens/profile/profile_screen.dart';
import '../screens/profile/favorites_screen.dart';
import '../screens/profile/edit_profile_screen.dart';
import '../screens/notifications/notifications_screen.dart';

class AppRouter {
  final AuthRepository _authRepo;

  AppRouter(this._authRepo);

  late final GoRouter router = GoRouter(
    initialLocation: '/splash',
    redirect: (context, state) async {
      final isLoggedIn = await _authRepo.isLoggedIn();
      final isAuthRoute = state.matchedLocation.startsWith('/auth');
      final isSplash = state.matchedLocation == '/splash';

      if (isSplash) return null;
      if (!isLoggedIn && !isAuthRoute) return '/auth/phone';

      return null;
    },
    routes: [
      GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),

      // Auth routes
      GoRoute(
        path: '/auth',
        redirect: (_, __) => '/auth/onboarding',
      ),
      GoRoute(path: '/auth/onboarding', builder: (_, __) => const OnboardingScreen()),
      GoRoute(path: '/auth/phone', builder: (_, __) => const PhoneScreen()),
      GoRoute(
        path: '/auth/otp',
        builder: (_, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return OtpScreen(
            phone: extra?['phone'] ?? '',
            type: extra?['type'] ?? 'login',
          );
        },
      ),
      GoRoute(
        path: '/auth/register',
        builder: (_, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return RegisterScreen(phone: extra?['phone'] ?? '');
        },
      ),

      // Main Shell (Bottom Nav)
      ShellRoute(
        builder: (context, state, child) => MainShell(child: child),
        routes: [
          GoRoute(
            path: '/home',
            builder: (_, __) => const HomeScreen(),
          ),
          GoRoute(
            path: '/venues',
            builder: (_, __) => const VenuesListScreen(),
          ),
          GoRoute(
            path: '/bookings',
            builder: (_, __) => const BookingListScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (_, __) => const ProfileScreen(),
          ),
        ],
      ),

      // Venue Detail
      GoRoute(
        path: '/venues/:id',
        builder: (_, state) => VenueDetailScreen(
          venueId: int.parse(state.pathParameters['id']!),
        ),
      ),

      // Map Screen
      GoRoute(
        path: '/map',
        builder: (_, __) => const VenueMapScreen(),
      ),

      // Booking
      GoRoute(
        path: '/booking/new',
        builder: (_, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return BookingScreen(venueId: extra?['venue_id'] ?? 0);
        },
      ),
      GoRoute(
        path: '/bookings/:id',
        builder: (_, state) => BookingDetailScreen(
          bookingId: int.parse(state.pathParameters['id']!),
        ),
      ),

      // Payment
      GoRoute(
        path: '/payment',
        builder: (_, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return PaymentScreen(
            bookingId: extra?['booking_id'] ?? 0,
            amount: (extra?['amount'] ?? 0).toDouble(),
          );
        },
      ),
      GoRoute(
        path: '/payment/success',
        builder: (_, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return PaymentSuccessScreen(bookingNumber: extra?['booking_number'] ?? '');
        },
      ),

      // Profile sub-screens
      GoRoute(path: '/favorites', builder: (_, __) => const FavoritesScreen()),
      GoRoute(path: '/profile/edit', builder: (_, __) => const EditProfileScreen()),
      GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
    ],
    errorBuilder: (context, state) => Scaffold(
      body: Center(child: Text('Sahifa topilmadi: ${state.uri}')),
    ),
  );
}
