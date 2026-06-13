import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/venue_model.dart';
import '../../../data/repositories/venue_repository.dart';
import '../../widgets/venue/venue_card.dart';

class FavoritesScreen extends StatefulWidget {
  const FavoritesScreen({super.key});

  @override
  State<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends State<FavoritesScreen> {
  List<VenueModel> _venues = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final venues = await repo.getFavorites();
      if (mounted) setState(() { _venues = venues; _isLoading = false; });
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sevimlilarim')),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _venues.isEmpty
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text('❤️', style: TextStyle(fontSize: 64)),
                      const SizedBox(height: 16),
                      Text('Sevimlilar yo\'q', style: AppTextStyles.h5),
                      const SizedBox(height: 8),
                      Text('Yoqtirgan maydonlaringizni saqlab qo\'ying!', style: AppTextStyles.body2),
                    ],
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _venues.length,
                  itemBuilder: (_, i) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: VenueCard(
                      venue: _venues[i],
                      onTap: () => context.push('/venues/${_venues[i].id}'),
                    ),
                  ),
                ),
    );
  }
}
