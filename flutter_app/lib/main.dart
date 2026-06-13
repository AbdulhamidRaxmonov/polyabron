import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'core/theme/app_theme.dart';
import 'core/network/api_client.dart';
import 'core/constants/app_constants.dart';
import 'data/repositories/auth_repository.dart';
import 'data/repositories/venue_repository.dart';
import 'data/repositories/booking_repository.dart';
import 'presentation/screens/splash_screen.dart';
import 'presentation/router/app_router.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
    ),
  );

  runApp(const OynaaApp());
}

class OynaaApp extends StatelessWidget {
  const OynaaApp({super.key});

  @override
  Widget build(BuildContext context) {
    final apiClient = ApiClient();
    const storage = FlutterSecureStorage();
    final authRepo = AuthRepository(apiClient, storage);
    final venueRepo = VenueRepository(apiClient);
    final bookingRepo = BookingRepository(apiClient);
    final router = AppRouter(authRepo).router;

    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: authRepo),
        RepositoryProvider.value(value: venueRepo),
        RepositoryProvider.value(value: bookingRepo),
      ],
      child: MaterialApp.router(
        title: AppConstants.appName,
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        routerConfig: router,
      ),
    );
  }
}
