import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/venue_model.dart';

class VenueCard extends StatelessWidget {
  final VenueModel venue;
  final VoidCallback onTap;
  final bool compact;

  const VenueCard({
    super.key,
    required this.venue,
    required this.onTap,
    this.compact = false,
  });

  @override
  Widget build(BuildContext context) {
    if (compact) return _buildCompact();
    return _buildFull();
  }

  Widget _buildFull() {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.border),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)],
        ),
        child: Row(
          children: [
            // Image
            ClipRRect(
              borderRadius: const BorderRadius.horizontal(left: Radius.circular(16)),
              child: venue.coverImage != null
                  ? CachedNetworkImage(
                      imageUrl: venue.coverImage!,
                      width: 110,
                      height: 100,
                      fit: BoxFit.cover,
                      placeholder: (_, __) => Container(color: AppTheme.border, width: 110, height: 100),
                    )
                  : Container(
                      width: 110,
                      height: 100,
                      color: AppTheme.primary.withOpacity(0.1),
                      child: const Icon(Icons.sports_soccer, color: AppTheme.textHint, size: 36),
                    ),
            ),

            // Info
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (venue.category != null)
                      Text(venue.category!.name,
                          style: AppTextStyles.caption.copyWith(color: AppTheme.primary, fontWeight: FontWeight.w600)),
                    const SizedBox(height: 2),
                    Text(venue.name, style: AppTextStyles.body1.copyWith(fontWeight: FontWeight.w600),
                        maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Icon(Icons.location_on_outlined, size: 12, color: AppTheme.textHint),
                        const SizedBox(width: 2),
                        Expanded(
                          child: Text(venue.address,
                              style: AppTextStyles.caption,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        const Icon(Icons.star_rounded, color: AppTheme.warning, size: 14),
                        const SizedBox(width: 2),
                        Text('${venue.rating.toStringAsFixed(1)} (${venue.reviewsCount})',
                            style: AppTextStyles.caption),
                        const Spacer(),
                        Text(venue.formattedPrice, style: AppTextStyles.price.copyWith(fontSize: 13)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCompact() {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppTheme.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Image
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                child: venue.coverImage != null
                    ? CachedNetworkImage(
                        imageUrl: venue.coverImage!,
                        width: double.infinity,
                        fit: BoxFit.cover,
                        placeholder: (_, __) => Container(color: AppTheme.border),
                      )
                    : Container(
                        color: AppTheme.primary.withOpacity(0.1),
                        child: const Center(child: Icon(Icons.sports_soccer, color: AppTheme.textHint, size: 36)),
                      ),
              ),
            ),

            // Info
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(venue.name,
                      style: AppTextStyles.caption.copyWith(fontWeight: FontWeight.w700, color: AppTheme.textPrimary),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.star_rounded, color: AppTheme.warning, size: 12),
                      const SizedBox(width: 2),
                      Text('${venue.rating.toStringAsFixed(1)}', style: AppTextStyles.caption),
                      const Spacer(),
                      if (venue.distance != null)
                        Text(venue.formattedDistance, style: AppTextStyles.caption),
                    ],
                  ),
                  Text(venue.formattedPrice, style: AppTextStyles.price.copyWith(fontSize: 12)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
