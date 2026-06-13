import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';

class AppSnackbar {
  static void success(BuildContext context, String message) {
    _show(context, message, AppTheme.success, Icons.check_circle_outline_rounded);
  }

  static void error(BuildContext context, String message) {
    _show(context, message, AppTheme.danger, Icons.error_outline_rounded);
  }

  static void info(BuildContext context, String message) {
    _show(context, message, AppTheme.info, Icons.info_outline_rounded);
  }

  static void warning(BuildContext context, String message) {
    _show(context, message, AppTheme.warning, Icons.warning_amber_rounded);
  }

  static void _show(BuildContext context, String message, Color color, IconData icon) {
    ScaffoldMessenger.of(context).hideCurrentSnackBar();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(icon, color: Colors.white, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
              ),
            ),
          ],
        ),
        backgroundColor: color,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
        duration: const Duration(seconds: 3),
      ),
    );
  }
}
