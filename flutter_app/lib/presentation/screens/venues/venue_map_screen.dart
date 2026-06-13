import 'dart:async';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/models/venue_model.dart';
import '../../../data/repositories/venue_repository.dart';

class VenueMapScreen extends StatefulWidget {
  const VenueMapScreen({super.key});

  @override
  State<VenueMapScreen> createState() => _VenueMapScreenState();
}

class _VenueMapScreenState extends State<VenueMapScreen> {
  final Completer<GoogleMapController> _mapController = Completer();
  List<VenueModel> _venues = [];
  Set<Marker> _markers = {};
  VenueModel? _selectedVenue;
  bool _isLoading = true;
  double _userLat = AppConstants.defaultLat;
  double _userLng = AppConstants.defaultLng;

  // Custom marker colors by category
  final Map<String, BitmapDescriptor> _markerIcons = {};

  @override
  void initState() {
    super.initState();
    _initLocation();
  }

  Future<void> _initLocation() async {
    try {
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        await Geolocator.requestPermission();
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      setState(() {
        _userLat = position.latitude;
        _userLng = position.longitude;
      });

      await _loadNearbyVenues();

      // Move camera to user location
      final controller = await _mapController.future;
      await controller.animateCamera(
        CameraUpdate.newCameraPosition(
          CameraPosition(
            target: LatLng(_userLat, _userLng),
            zoom: AppConstants.defaultZoom,
          ),
        ),
      );
    } catch (e) {
      await _loadNearbyVenues();
    }
  }

  Future<void> _loadNearbyVenues() async {
    setState(() => _isLoading = true);
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final venues = await repo.getNearbyVenues(
        _userLat,
        _userLng,
        radius: 20,
      );

      setState(() {
        _venues = venues;
        _isLoading = false;
        _buildMarkers();
      });
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _buildMarkers() {
    final markers = <Marker>{};

    // User location marker
    markers.add(Marker(
      markerId: const MarkerId('user_location'),
      position: LatLng(_userLat, _userLng),
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
      infoWindow: const InfoWindow(title: 'Siz shu yerdasisiz'),
    ));

    // Venue markers
    for (final venue in _venues) {
      markers.add(Marker(
        markerId: MarkerId('venue_${venue.id}'),
        position: LatLng(venue.latitude, venue.longitude),
        icon: BitmapDescriptor.defaultMarkerWithHue(
          _selectedVenue?.id == venue.id
              ? BitmapDescriptor.hueViolet
              : BitmapDescriptor.hueRed,
        ),
        infoWindow: InfoWindow(
          title: venue.name,
          snippet: venue.formattedPrice,
          onTap: () => context.push('/venues/${venue.id}'),
        ),
        onTap: () => setState(() => _selectedVenue = venue),
      ));
    }

    setState(() => _markers = markers);
  }

  Future<void> _moveTo(double lat, double lng) async {
    final controller = await _mapController.future;
    await controller.animateCamera(
      CameraUpdate.newLatLngZoom(LatLng(lat, lng), 16),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Xaritada izlash'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded),
          onPressed: () => context.pop(),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.my_location_rounded),
            onPressed: () => _moveTo(_userLat, _userLng),
          ),
        ],
      ),
      body: Stack(
        children: [
          // Google Map
          GoogleMap(
            onMapCreated: (controller) => _mapController.complete(controller),
            initialCameraPosition: CameraPosition(
              target: LatLng(_userLat, _userLng),
              zoom: AppConstants.defaultZoom,
            ),
            markers: _markers,
            myLocationEnabled: true,
            myLocationButtonEnabled: false,
            zoomControlsEnabled: false,
            mapToolbarEnabled: false,
            onTap: (_) => setState(() => _selectedVenue = null),
          ),

          // Loading
          if (_isLoading)
            const Center(child: Card(child: Padding(
              padding: EdgeInsets.all(20),
              child: CircularProgressIndicator(),
            ))),

          // Venue count badge
          Positioned(
            top: 16,
            left: 16,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 8)],
              ),
              child: Row(
                children: [
                  const Icon(Icons.location_on_rounded, color: AppTheme.primary, size: 18),
                  const SizedBox(width: 6),
                  Text('${_venues.length} maydon topildi',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                ],
              ),
            ),
          ),

          // Selected venue card
          if (_selectedVenue != null)
            Positioned(
              bottom: 24,
              left: 16,
              right: 16,
              child: _VenueMapCard(
                venue: _selectedVenue!,
                onTap: () => context.push('/venues/${_selectedVenue!.id}'),
                onBook: () => context.push('/booking/new',
                    extra: {'venue_id': _selectedVenue!.id}),
              ),
            ),

          // Venues list (collapsed)
          if (_selectedVenue == null && _venues.isNotEmpty)
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: _VenueListSheet(
                venues: _venues,
                onVenueTap: (v) {
                  setState(() => _selectedVenue = v);
                  _moveTo(v.latitude, v.longitude);
                  _buildMarkers();
                },
              ),
            ),
        ],
      ),
    );
  }
}

class _VenueMapCard extends StatelessWidget {
  final VenueModel venue;
  final VoidCallback onTap;
  final VoidCallback onBook;

  const _VenueMapCard({required this.venue, required this.onTap, required this.onBook});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.12), blurRadius: 20, offset: const Offset(0, 4))],
        ),
        child: Row(
          children: [
            // Image
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: venue.coverImage != null
                  ? Image.network(venue.coverImage!, width: 72, height: 72, fit: BoxFit.cover)
                  : Container(
                      width: 72,
                      height: 72,
                      color: AppTheme.primary.withOpacity(0.1),
                      child: const Icon(Icons.sports_soccer, color: AppTheme.textHint),
                    ),
            ),
            const SizedBox(width: 12),

            // Info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(venue.name, style: AppTextStyles.h5, maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star_rounded, color: AppTheme.warning, size: 14),
                      const SizedBox(width: 2),
                      Text('${venue.rating.toStringAsFixed(1)}', style: AppTextStyles.caption),
                      const SizedBox(width: 8),
                      const Icon(Icons.location_on_outlined, color: AppTheme.danger, size: 14),
                      Text(venue.formattedDistance, style: AppTextStyles.caption),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(venue.formattedPrice, style: AppTextStyles.price.copyWith(fontSize: 14)),
                ],
              ),
            ),
            const SizedBox(width: 8),

            // Book button
            ElevatedButton(
              onPressed: onBook,
              style: ElevatedButton.styleFrom(
                minimumSize: const Size(0, 36),
                padding: const EdgeInsets.symmetric(horizontal: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text('Bron', style: TextStyle(fontSize: 12)),
            ),
          ],
        ),
      ),
    );
  }
}

class _VenueListSheet extends StatelessWidget {
  final List<VenueModel> venues;
  final void Function(VenueModel) onVenueTap;

  const _VenueListSheet({required this.venues, required this.onVenueTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 100,
      color: Colors.transparent,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: venues.length,
        itemBuilder: (_, i) {
          final v = venues[i];
          return GestureDetector(
            onTap: () => onVenueTap(v),
            child: Container(
              width: 180,
              margin: const EdgeInsets.only(right: 10, bottom: 16),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 8)],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(v.name, style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600),
                      maxLines: 1, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 4),
                  Text(v.formattedPrice, style: AppTextStyles.price.copyWith(fontSize: 13)),
                  Text(v.formattedDistance, style: AppTextStyles.caption),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
