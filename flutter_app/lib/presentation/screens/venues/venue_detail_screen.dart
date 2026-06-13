import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import 'package:readmore/readmore.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/venue_model.dart';
import '../../../data/repositories/venue_repository.dart';
import '../../widgets/common/app_snackbar.dart';

class VenueDetailScreen extends StatefulWidget {
  final int venueId;
  const VenueDetailScreen({super.key, required this.venueId});

  @override
  State<VenueDetailScreen> createState() => _VenueDetailScreenState();
}

class _VenueDetailScreenState extends State<VenueDetailScreen> {
  VenueModel? _venue;
  bool _isLoading = true;
  bool _isFavoriteLoading = false;
  int _currentImage = 0;

  @override
  void initState() {
    super.initState();
    _loadVenue();
  }

  Future<void> _loadVenue() async {
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final venue = await repo.getVenueDetail(widget.venueId);
      if (mounted) setState(() { _venue = venue; _isLoading = false; });
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _toggleFavorite() async {
    if (_venue == null || _isFavoriteLoading) return;
    setState(() => _isFavoriteLoading = true);
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final isFav = await repo.toggleFavorite(_venue!.id);
      if (!mounted) return;
      setState(() {
        _venue = VenueModel(
          id: _venue!.id,
          name: _venue!.name,
          description: _venue!.description,
          address: _venue!.address,
          latitude: _venue!.latitude,
          longitude: _venue!.longitude,
          city: _venue!.city,
          phone: _venue!.phone,
          pricePerHour: _venue!.pricePerHour,
          priceWeekend: _venue!.priceWeekend,
          openTime: _venue!.openTime,
          closeTime: _venue!.closeTime,
          rating: _venue!.rating,
          reviewsCount: _venue!.reviewsCount,
          bookingsCount: _venue!.bookingsCount,
          isFeatured: _venue!.isFeatured,
          amenities: _venue!.amenities,
          coverImage: _venue!.coverImage,
          images: _venue!.images,
          category: _venue!.category,
          distance: _venue!.distance,
          isFavorite: isFav,
        );
      });
      AppSnackbar.info(context, isFav ? 'Sevimlilarga qo\'shildi' : 'Sevimlilardab o\'chirildi');
    } catch (_) {
      AppSnackbar.error(context, 'Xatolik yuz berdi');
    } finally {
      if (mounted) setState(() => _isFavoriteLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_venue == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: Text('Maydon topilmadi')),
      );
    }

    final v = _venue!;
    final allImages = [if (v.coverImage != null) v.coverImage!, ...v.images];

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // Image Header
          SliverAppBar(
            expandedHeight: 280,
            pinned: true,
            leading: _CircleBack(),
            actions: [
              _CircleIconButton(
                icon: v.isFavorite == true
                    ? Icons.favorite_rounded
                    : Icons.favorite_border_rounded,
                color: v.isFavorite == true ? AppTheme.danger : AppTheme.textSecondary,
                onTap: _toggleFavorite,
              ),
              const SizedBox(width: 8),
              _CircleIconButton(
                icon: Icons.share_outlined,
                onTap: () {},
              ),
              const SizedBox(width: 12),
            ],
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  allImages.isNotEmpty
                      ? PageView.builder(
                          itemCount: allImages.length,
                          onPageChanged: (i) => setState(() => _currentImage = i),
                          itemBuilder: (_, i) => CachedNetworkImage(
                            imageUrl: allImages[i],
                            fit: BoxFit.cover,
                            placeholder: (_, __) => Container(color: AppTheme.border),
                          ),
                        )
                      : Container(
                          color: AppTheme.primary.withOpacity(0.1),
                          child: const Icon(Icons.sports_soccer, size: 80, color: AppTheme.textHint),
                        ),
                  // Page indicator
                  if (allImages.length > 1)
                    Positioned(
                      bottom: 16,
                      left: 0,
                      right: 0,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(
                          allImages.length,
                          (i) => Container(
                            margin: const EdgeInsets.symmetric(horizontal: 3),
                            width: i == _currentImage ? 20 : 6,
                            height: 6,
                            decoration: BoxDecoration(
                              color: i == _currentImage
                                  ? Colors.white
                                  : Colors.white.withOpacity(0.5),
                              borderRadius: BorderRadius.circular(3),
                            ),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),

          // Content
          SliverToBoxAdapter(
            child: Container(
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Name & Category
                        Row(
                          children: [
                            if (v.category != null)
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: AppTheme.primary.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  v.category!.name,
                                  style: const TextStyle(color: AppTheme.primary, fontSize: 12, fontWeight: FontWeight.w600),
                                ),
                              ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        Text(v.name, style: AppTextStyles.h3),
                        const SizedBox(height: 8),

                        // Rating & Reviews
                        Row(
                          children: [
                            RatingBarIndicator(
                              rating: v.rating,
                              itemBuilder: (_, __) => const Icon(Icons.star_rounded, color: AppTheme.warning),
                              itemCount: 5,
                              itemSize: 18,
                            ),
                            const SizedBox(width: 6),
                            Text('${v.rating.toStringAsFixed(1)} (${v.reviewsCount} izoh)',
                                style: AppTextStyles.body2),
                            const Spacer(),
                            const Icon(Icons.calendar_today_outlined, size: 16, color: AppTheme.textHint),
                            const SizedBox(width: 4),
                            Text('${v.bookingsCount} bron', style: AppTextStyles.body2),
                          ],
                        ),
                        const SizedBox(height: 12),

                        // Address
                        Row(
                          children: [
                            const Icon(Icons.location_on_outlined, size: 18, color: AppTheme.danger),
                            const SizedBox(width: 6),
                            Expanded(child: Text(v.address, style: AppTextStyles.body2)),
                            if (v.distance != null)
                              Text(v.formattedDistance, style: AppTextStyles.caption),
                          ],
                        ),
                        const SizedBox(height: 8),

                        // Working hours
                        Row(
                          children: [
                            const Icon(Icons.access_time_outlined, size: 18, color: AppTheme.success),
                            const SizedBox(width: 6),
                            Text('${v.openTime.substring(0,5)} – ${v.closeTime.substring(0,5)}',
                                style: AppTextStyles.body2),
                            const SizedBox(width: 16),
                            if (v.phone != null) ...[
                              const Icon(Icons.phone_outlined, size: 18, color: AppTheme.info),
                              const SizedBox(width: 6),
                              Text(v.phone!, style: AppTextStyles.body2),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),

                  // Price
                  Container(
                    margin: const EdgeInsets.all(16),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [AppTheme.primary, AppTheme.primaryLight]),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Row(
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Narx', style: TextStyle(color: Colors.white70, fontSize: 12)),
                            Text(
                              v.formattedPrice,
                              style: const TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w700),
                            ),
                            if (v.priceWeekend != null)
                              Text('Dam olish kuni: ${(v.priceWeekend! / 1000).toStringAsFixed(0)}K so\'m',
                                  style: const TextStyle(color: Colors.white70, fontSize: 12)),
                          ],
                        ),
                        const Spacer(),
                        ElevatedButton(
                          onPressed: () => context.push('/booking/new', extra: {'venue_id': v.id}),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: AppTheme.primary,
                            minimumSize: const Size(0, 44),
                            padding: const EdgeInsets.symmetric(horizontal: 20),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          child: const Text('Bron qilish', style: TextStyle(fontWeight: FontWeight.w700)),
                        ),
                      ],
                    ),
                  ),

                  // Description
                  if (v.description != null && v.description!.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Tavsif', style: AppTextStyles.h5),
                          const SizedBox(height: 8),
                          ReadMoreText(
                            v.description!,
                            trimLines: 3,
                            colorClickableText: AppTheme.primary,
                            trimMode: TrimMode.Line,
                            style: AppTextStyles.body2.copyWith(height: 1.6),
                          ),
                          const SizedBox(height: 16),
                        ],
                      ),
                    ),

                  // Amenities
                  if (v.amenities.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Imkoniyatlar', style: AppTextStyles.h5),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: v.amenities.map((a) => _AmenityChip(label: a)).toList(),
                          ),
                          const SizedBox(height: 16),
                        ],
                      ),
                    ),

                  const Divider(height: 1, indent: 20, endIndent: 20),
                  const SizedBox(height: 100),
                ],
              ),
            ),
          ),
        ],
      ),

      // Bottom Bar
      bottomNavigationBar: Container(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 16, offset: const Offset(0, -4))],
        ),
        child: ElevatedButton.icon(
          onPressed: () => context.push('/booking/new', extra: {'venue_id': v.id}),
          icon: const Icon(Icons.calendar_month_rounded),
          label: const Text('Bron qilish'),
        ),
      ),
    );
  }
}

class _CircleBack extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(8),
      child: GestureDetector(
        onTap: () => context.pop(),
        child: Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.9),
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.arrow_back_ios_new_rounded, size: 18),
        ),
      ),
    );
  }
}

class _CircleIconButton extends StatelessWidget {
  final IconData icon;
  final Color? color;
  final VoidCallback onTap;

  const _CircleIconButton({required this.icon, this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.9),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, size: 20, color: color ?? AppTheme.textPrimary),
      ),
    );
  }
}

class _AmenityChip extends StatelessWidget {
  final String label;
  const _AmenityChip({required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.background,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppTheme.border),
      ),
      child: Text(label, style: AppTextStyles.caption.copyWith(color: AppTheme.textSecondary)),
    );
  }
}
