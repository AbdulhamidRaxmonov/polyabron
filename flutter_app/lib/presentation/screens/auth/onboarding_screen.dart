import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final PageController _pageController = PageController();
  int _currentPage = 0;

  final List<_OnboardPage> _pages = [
    const _OnboardPage(
      emoji: '🏟️',
      title: 'Sport maydonlarini toping',
      subtitle: 'Yaqin atrofdagi futbol, tennis, basketbol va boshqa sport maydonlarini xaritada oson toping.',
      color: AppTheme.primary,
    ),
    const _OnboardPage(
      emoji: '📅',
      title: 'Bir daqiqada bron qiling',
      subtitle: 'Qulay vaqtni tanlang va darhol bron qiling. Hech qanday qo\'ng\'iroq kerak emas!',
      color: Color(0xFF00CEC9),
    ),
    const _OnboardPage(
      emoji: '💳',
      title: 'Xavfsiz to\'lov',
      subtitle: 'Payme va Click orqali xavfsiz va tez to\'lov qiling. Pul qaytarish kafolati bilan.',
      color: Color(0xFF6C5CE7),
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            // Skip
            Align(
              alignment: Alignment.topRight,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: TextButton(
                  onPressed: () => context.go('/auth/phone'),
                  child: const Text('O\'tkazib yuborish',
                      style: TextStyle(color: AppTheme.textSecondary)),
                ),
              ),
            ),

            // Pages
            Expanded(
              child: PageView.builder(
                controller: _pageController,
                itemCount: _pages.length,
                onPageChanged: (i) => setState(() => _currentPage = i),
                itemBuilder: (_, i) => _OnboardPageWidget(page: _pages[i]),
              ),
            ),

            // Indicators
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                _pages.length,
                (i) => AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  margin: const EdgeInsets.symmetric(horizontal: 4),
                  width: i == _currentPage ? 28 : 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: i == _currentPage
                        ? AppTheme.primary
                        : AppTheme.border,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 32),

            // Button
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: ElevatedButton(
                onPressed: () {
                  if (_currentPage < _pages.length - 1) {
                    _pageController.nextPage(
                      duration: const Duration(milliseconds: 300),
                      curve: Curves.easeInOut,
                    );
                  } else {
                    context.go('/auth/phone');
                  }
                },
                child: Text(
                  _currentPage < _pages.length - 1 ? 'Davom etish' : 'Boshlash',
                ),
              ),
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }
}

class _OnboardPage {
  final String emoji;
  final String title;
  final String subtitle;
  final Color color;

  const _OnboardPage({
    required this.emoji,
    required this.title,
    required this.subtitle,
    required this.color,
  });
}

class _OnboardPageWidget extends StatelessWidget {
  final _OnboardPage page;

  const _OnboardPageWidget({required this.page});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 160,
            height: 160,
            decoration: BoxDecoration(
              color: page.color.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(page.emoji, style: const TextStyle(fontSize: 72)),
            ),
          ),
          const SizedBox(height: 40),
          Text(
            page.title,
            style: AppTextStyles.h2.copyWith(textAlign: TextAlign.center),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          Text(
            page.subtitle,
            style: AppTextStyles.body2.copyWith(height: 1.6),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}
