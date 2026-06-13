import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../../../data/models/user_model.dart';
import '../../../data/repositories/auth_repository.dart';
import 'package:intl/intl.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  UserModel? _user;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final repo = RepositoryProvider.of<AuthRepository>(context);
    final user = await repo.getCurrentUser();
    if (mounted) setState(() { _user = user; _isLoading = false; });
  }

  Future<void> _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Chiqish'),
        content: const Text('Tizimdan chiqmoqchimisiz?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Yo\'q')),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.danger, minimumSize: const Size(0, 36)),
            child: const Text('Ha, chiqish'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      final repo = RepositoryProvider.of<AuthRepository>(context);
      await repo.logout();
      if (!mounted) return;
      context.go('/auth/phone');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) return const Scaffold(body: Center(child: CircularProgressIndicator()));

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // Header
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.fromLTRB(20, 60, 20, 24),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [AppTheme.primary, AppTheme.primaryLight],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              child: Row(
                children: [
                  // Avatar
                  GestureDetector(
                    onTap: () => context.push('/profile/edit'),
                    child: Stack(
                      children: [
                        Container(
                          width: 72,
                          height: 72,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Colors.white,
                            image: _user?.avatar != null
                                ? DecorationImage(
                                    image: NetworkImage(_user!.avatar!),
                                    fit: BoxFit.cover,
                                  )
                                : null,
                          ),
                          child: _user?.avatar == null
                              ? Center(
                                  child: Text(
                                    _user?.name.substring(0, 1).toUpperCase() ?? 'A',
                                    style: const TextStyle(
                                      fontSize: 28,
                                      fontWeight: FontWeight.w800,
                                      color: AppTheme.primary,
                                    ),
                                  ),
                                )
                              : null,
                        ),
                        Positioned(
                          right: 0,
                          bottom: 0,
                          child: Container(
                            width: 22,
                            height: 22,
                            decoration: const BoxDecoration(
                              color: Colors.white,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.edit_rounded, size: 12, color: AppTheme.primary),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(_user?.name ?? '', style: const TextStyle(
                          color: Colors.white, fontSize: 20, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        Text(_user?.phone ?? '',
                            style: const TextStyle(color: Colors.white70, fontSize: 14)),
                      ],
                    ),
                  ),
                  // Balance
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      const Text('Balans', style: TextStyle(color: Colors.white60, fontSize: 11)),
                      Text(
                        '${NumberFormat('#,###').format(_user?.balance ?? 0)} so\'m',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 15),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          // Menu Items
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  _MenuSection(
                    title: 'Mening',
                    items: [
                      _MenuItem(icon: Icons.calendar_month_rounded, label: 'Bronlarim', color: AppTheme.primary,
                          onTap: () => context.go('/bookings')),
                      _MenuItem(icon: Icons.favorite_rounded, label: 'Sevimlilarim', color: AppTheme.danger,
                          onTap: () => context.push('/favorites')),
                      _MenuItem(icon: Icons.notifications_rounded, label: 'Bildirishnomalar', color: AppTheme.warning,
                          onTap: () => context.push('/notifications')),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _MenuSection(
                    title: 'Sozlamalar',
                    items: [
                      _MenuItem(icon: Icons.person_outline_rounded, label: 'Profilni tahrirlash', color: AppTheme.info,
                          onTap: () => context.push('/profile/edit')),
                      _MenuItem(icon: Icons.lock_outline_rounded, label: 'Parol o\'zgartirish', color: AppTheme.secondary,
                          onTap: () {}),
                      _MenuItem(icon: Icons.language_rounded, label: 'Til: O\'zbek', color: AppTheme.success,
                          onTap: () {}),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _MenuSection(
                    title: 'Qo\'llab quvvatlash',
                    items: [
                      _MenuItem(icon: Icons.help_outline_rounded, label: 'Yordam markazi', color: AppTheme.textSecondary,
                          onTap: () {}),
                      _MenuItem(icon: Icons.privacy_tip_outlined, label: 'Maxfiylik siyosati', color: AppTheme.textSecondary,
                          onTap: () {}),
                      _MenuItem(icon: Icons.info_outline_rounded, label: 'Ilova haqida', color: AppTheme.textSecondary,
                          onTap: () {}),
                    ],
                  ),
                  const SizedBox(height: 12),

                  // Logout
                  GestureDetector(
                    onTap: _logout,
                    child: Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppTheme.danger.withOpacity(0.05),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppTheme.danger.withOpacity(0.2)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 40,
                            height: 40,
                            decoration: BoxDecoration(
                              color: AppTheme.danger.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.logout_rounded, color: AppTheme.danger, size: 20),
                          ),
                          const SizedBox(width: 14),
                          const Text('Tizimdan chiqish',
                              style: TextStyle(color: AppTheme.danger, fontWeight: FontWeight.w600)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MenuSection extends StatelessWidget {
  final String title;
  final List<_MenuItem> items;

  const _MenuSection({required this.title, required this.items});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: Text(title,
                style: AppTextStyles.caption.copyWith(fontWeight: FontWeight.w600, letterSpacing: .5)),
          ),
          ...items.map((item) => _MenuItemWidget(item: item)),
        ],
      ),
    );
  }
}

class _MenuItem {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  final Widget? trailing;

  const _MenuItem({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
    this.trailing,
  });
}

class _MenuItemWidget extends StatelessWidget {
  final _MenuItem item;
  const _MenuItemWidget({required this.item});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: item.onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                color: item.color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(item.icon, color: item.color, size: 20),
            ),
            const SizedBox(width: 14),
            Expanded(child: Text(item.label, style: AppTextStyles.body1)),
            item.trailing ?? const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: AppTheme.textHint),
          ],
        ),
      ),
    );
  }
}
