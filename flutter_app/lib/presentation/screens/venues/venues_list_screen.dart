import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/venue_model.dart';
import '../../../data/repositories/venue_repository.dart';
import '../../widgets/venue/venue_card.dart';
import '../../widgets/common/shimmer_loader.dart';

class VenuesListScreen extends StatefulWidget {
  const VenuesListScreen({super.key});

  @override
  State<VenuesListScreen> createState() => _VenuesListScreenState();
}

class _VenuesListScreenState extends State<VenuesListScreen> {
  final _searchController = TextEditingController();
  List<VenueModel> _venues = [];
  List<CategoryModel> _categories = [];
  bool _isLoading = true;
  int? _selectedCategoryId;
  String _sortBy = 'rating';
  int _page = 1;
  bool _hasMore = true;
  bool _isLoadingMore = false;
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadCategories();
    _loadVenues(reset: true);
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200 &&
        !_isLoadingMore && _hasMore) {
      _loadMoreVenues();
    }
  }

  Future<void> _loadCategories() async {
    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final cats = await repo.getCategories();
      if (mounted) setState(() => _categories = cats);
    } catch (_) {}
  }

  Future<void> _loadVenues({bool reset = false}) async {
    if (reset) { _page = 1; _hasMore = true; }
    setState(() => _isLoading = reset);

    try {
      final repo = RepositoryProvider.of<VenueRepository>(context);
      final result = await repo.getVenues(
        categoryId: _selectedCategoryId,
        search: _searchController.text.isNotEmpty ? _searchController.text : null,
        sortBy: _sortBy,
        page: _page,
      );

      final newVenues = result['venues'] as List<VenueModel>;
      final meta = result['meta'];

      if (mounted) {
        setState(() {
          if (reset) _venues = newVenues;
          else _venues.addAll(newVenues);
          _hasMore = meta['current_page'] < meta['last_page'];
          _isLoading = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _loadMoreVenues() async {
    setState(() => _isLoadingMore = true);
    _page++;
    await _loadVenues();
    if (mounted) setState(() => _isLoadingMore = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Maydonlar'),
        actions: [
          IconButton(
            icon: const Icon(Icons.map_outlined),
            onPressed: () => context.push('/map'),
          ),
        ],
      ),
      body: Column(
        children: [
          // Search
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: TextField(
              controller: _searchController,
              onSubmitted: (_) => _loadVenues(reset: true),
              decoration: InputDecoration(
                hintText: 'Maydon qidirish...',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.close_rounded),
                        onPressed: () {
                          _searchController.clear();
                          _loadVenues(reset: true);
                        },
                      )
                    : null,
              ),
            ),
          ),

          // Categories
          if (_categories.isNotEmpty)
            SizedBox(
              height: 48,
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                scrollDirection: Axis.horizontal,
                itemCount: _categories.length + 1,
                itemBuilder: (_, i) {
                  final isAll = i == 0;
                  final cat = isAll ? null : _categories[i - 1];
                  final isSelected = isAll
                      ? _selectedCategoryId == null
                      : _selectedCategoryId == cat!.id;

                  return GestureDetector(
                    onTap: () {
                      setState(() => _selectedCategoryId = isAll ? null : cat!.id);
                      _loadVenues(reset: true);
                    },
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      margin: const EdgeInsets.only(right: 8),
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      decoration: BoxDecoration(
                        color: isSelected ? AppTheme.primary : AppTheme.background,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: isSelected ? AppTheme.primary : AppTheme.border),
                      ),
                      child: Center(
                        child: Text(
                          isAll ? 'Barchasi' : cat!.name,
                          style: TextStyle(
                            color: isSelected ? Colors.white : AppTheme.textSecondary,
                            fontSize: 12,
                            fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),

          // Sort
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Row(
              children: [
                Text('${_venues.length} maydon topildi', style: AppTextStyles.caption),
                const Spacer(),
                DropdownButton<String>(
                  value: _sortBy,
                  underline: const SizedBox(),
                  style: AppTextStyles.caption.copyWith(color: AppTheme.primary, fontWeight: FontWeight.w600),
                  items: const [
                    DropdownMenuItem(value: 'rating', child: Text('Reyting')),
                    DropdownMenuItem(value: 'price_asc', child: Text('Narx ↑')),
                    DropdownMenuItem(value: 'price_desc', child: Text('Narx ↓')),
                  ],
                  onChanged: (v) {
                    setState(() => _sortBy = v!);
                    _loadVenues(reset: true);
                  },
                ),
              ],
            ),
          ),

          // List
          Expanded(
            child: _isLoading
                ? const HomeShimmer()
                : _venues.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Text('🔍', style: TextStyle(fontSize: 48)),
                            const SizedBox(height: 12),
                            Text('Maydonlar topilmadi', style: AppTextStyles.h5),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () => _loadVenues(reset: true),
                        child: ListView.builder(
                          controller: _scrollController,
                          padding: const EdgeInsets.all(16),
                          itemCount: _venues.length + (_isLoadingMore ? 1 : 0),
                          itemBuilder: (_, i) {
                            if (i == _venues.length) {
                              return const Center(child: Padding(
                                padding: EdgeInsets.all(16),
                                child: CircularProgressIndicator(),
                              ));
                            }
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: VenueCard(
                                venue: _venues[i],
                                onTap: () => context.push('/venues/${_venues[i].id}'),
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}
