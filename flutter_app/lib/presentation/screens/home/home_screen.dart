import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:carousel_slider/carousel_slider.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/constants/app_constants.dart';
import '../../../data/models/venue_model.dart';
import '../../../data/repositories/venue_repository.dart';
import '../../widgets/venue/venue_card.dart';
import '../../widgets/common/section_header.dart';
import '../../widgets/common/shimmer_loader.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Map<String, dynamic>? _homeData;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadHome();
  }

  Future<void> _loadHome() async {
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final data = await repo.getHomeData();
      if (mounted) setState(() { _homeData = data; _isLoading = false; });
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _isLoading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadHome,
          color: AppTheme.primary,
          child: CustomScrollView(
            slivers: [
              // App Bar
              SliverToBoxAdapter(child: _buildHeader()),

              if (_isLoading)
                const SliverToBoxAdapter(child: HomeShimmer())
              else if (_error != null)
                SliverToBoxAdapter(child: _buildError())
              else ...[
                // Banners
                if (_homeData?['banners'] != null &&
                    (_homeData!['banners'] as List).isNotEmpty)
                  SliverToBoxAdapter(child: _buildBanners()),

                // Categories
                SliverToBoxAdapter(child: _buildCategories()),

                // Featured
                if (_homeData?['featured'] != null &&
                    (_homeData!['featured'] as List).isNotEmpty) ...[
                  SliverToBoxAdapter(
                    child: SectionHeader(
                      title: 'Tavsiya etilgan',
                      onSeeAll: () => context.go('/venues?featured=1'),
                    ),
                  ),
                  SliverToBoxAdapter(child: _buildHorizontalVenues('featured')),
                ],

                // Nearby
                if (_homeData?['nearby'] != null &&
                    (_homeData!['nearby'] as List).isNotEmpty) ...[
                  SliverToBoxAdapter(
                    child: SectionHeader(
                      title: 'Yaqin atrofdagi',
                      onSeeAll: () => context.go('/map'),
                    ),
                  ),
                  SliverToBoxAdapter(child: _buildHorizontalVenues('nearby')),
                ],

                // New venues
                SliverToBoxAdapter(
                  child: SectionHeader(
                    title: 'Yangi maydonlar',
                    onSeeAll: () => context.go('/venues'),
                  ),
                ),
                SliverPadding(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) {
                        final venues = (_homeData?['new_venues'] ?? []) as List;
                        if (index >= venues.length) return null;
                        final venue = VenueModel.fromJson(venues[index]);
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: VenueCard(
                            venue: venue,
                            onTap: () => context.push('/venues/${venue.id}'),
                          ),
                        );
                      },
                      childCount: (_homeData?['new_venues'] as List?)?.length ?? 0,
                    ),
                  ),
                ),
                const SliverToBoxAdapter(child: SizedBox(height: 24)),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 16, 8),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text("Assalomu alaykum! 👋",
                    style: AppTextStyles.body2),
                Text("O'ynaa", style: AppTextStyles.h2),
              ],
            ),
          ),
          IconButton(
            onPressed: () => context.push('/notifications'),
            icon: Stack(
              children: [
                const Icon(Icons.notifications_outlined, size: 28),
                Positioned(
                  right: 0,
                  top: 0,
                  child: Container(
                    width: 10,
                    height: 10,
                    decoration: const BoxDecoration(
                      color: AppTheme.danger,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 4),
          GestureDetector(
            onTap: () => context.push('/map'),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppTheme.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.map_outlined, color: AppTheme.primary),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBanners() {
    final banners = (_homeData!['banners'] as List);
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: CarouselSlider(
        options: CarouselOptions(
          height: 160,
          viewportFraction: 0.92,
          enlargeCenterPage: true,
          autoPlay: true,
          autoPlayInterval: const Duration(seconds: 4),
        ),
        items: banners.map((b) {
          return GestureDetector(
            onTap: () {
              if (b['venue_id'] != null) {
                context.push('/venues/${b['venue_id']}');
              }
            },
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: CachedNetworkImage(
                imageUrl: b['image'] ?? '',
                width: double.infinity,
                fit: BoxFit.cover,
                placeholder: (_, __) => Container(color: AppTheme.border),
                errorWidget: (_, __, ___) => Container(
                  color: AppTheme.primary.withOpacity(0.1),
                  child: const Icon(Icons.image_outlined, size: 40, color: AppTheme.textHint),
                ),
              ),
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildCategories() {
    final categories = (_homeData?['categories'] ?? []) as List;
    return SizedBox(
      height: 90,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: categories.length,
        itemBuilder: (_, i) {
          final cat = categories[i];
          return GestureDetector(
            onTap: () => context.go('/venues?category_id=${cat['id']}'),
            child: Container(
              margin: const EdgeInsets.only(right: 12),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: AppTheme.background,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppTheme.border),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    cat['icon'] ?? '⚽',
                    style: const TextStyle(fontSize: 28),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    cat['name'] ?? '',
                    style: AppTextStyles.caption.copyWith(fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildHorizontalVenues(String key) {
    final list = (_homeData?[key] ?? []) as List;
    return SizedBox(
      height: 220,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        scrollDirection: Axis.horizontal,
        itemCount: list.length,
        itemBuilder: (_, i) {
          final venue = VenueModel.fromJson(list[i]);
          return Padding(
            padding: const EdgeInsets.only(right: 12),
            child: SizedBox(
              width: 200,
              child: VenueCard(
                venue: venue,
                compact: true,
                onTap: () => context.push('/venues/${venue.id}'),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          children: [
            const Icon(Icons.wifi_off_rounded, size: 64, color: AppTheme.textHint),
            const SizedBox(height: 16),
            Text('Internet aloqasi yo\'q', style: AppTextStyles.h5),
            const SizedBox(height: 24),
            ElevatedButton(onPressed: _loadHome, child: const Text('Qayta urinish')),
          ],
        ),
      ),
    );
  }
}
