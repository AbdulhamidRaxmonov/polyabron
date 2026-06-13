<?php
/**
 * Bot setup script
 * Run once: php setup.php
 * Sets webhook URL and bot commands.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\{Config, TelegramBot};

Config::load();

$bot    = new TelegramBot();
$appUrl = rtrim(Config::get('app_url'), '/');
$secret = Config::get('webhook_secret');

echo "🤖 iCalCalorie Bot — Sozlash\n";
echo "================================\n\n";

// ── 1. Set webhook ─────────────────────────────────────────────────────────
echo "1️⃣  Webhook o'rnatilmoqda...\n";

$webhookUrl = $appUrl . '/webhook.php';
$result     = $bot->setWebhook($webhookUrl, $secret);

if ($result['ok'] ?? false) {
    echo "   ✅ Webhook o'rnatildi: {$webhookUrl}\n";
} else {
    echo "   ❌ Webhook xatosi: " . ($result['description'] ?? 'Noma\'lum') . "\n";
}

// ── 2. Webhook info ────────────────────────────────────────────────────────
echo "\n2️⃣  Webhook holati:\n";
$info = $bot->getWebhookInfo();
if ($info['ok'] ?? false) {
    $w = $info['result'];
    echo "   URL:              " . ($w['url']             ?? 'yo\'q') . "\n";
    echo "   Pending updates:  " . ($w['pending_update_count'] ?? 0) . "\n";
    echo "   Last error:       " . ($w['last_error_message'] ?? 'yo\'q') . "\n";
}

// ── 3. Set bot commands ────────────────────────────────────────────────────
echo "\n3️⃣  Bot buyruqlari o'rnatilmoqda...\n";

$commands = [
    ['command' => 'start',        'description' => '🏠 Botni ishga tushirish'],
    ['command' => 'add',          'description' => '🍽 Taom qo\'shish'],
    ['command' => 'diary',        'description' => '📋 Kundalik ko\'rish'],
    ['command' => 'water',        'description' => '💧 Suv qayd etish'],
    ['command' => 'weight',       'description' => '⚖️ Vazn kiritish'],
    ['command' => 'stats',        'description' => '📊 Statistika'],
    ['command' => 'profile',      'description' => '👤 Profil'],
    ['command' => 'settings',     'description' => '⚙️ Sozlamalar'],
    ['command' => 'myfoods',      'description' => '🍽 Mening taomlarim'],
    ['command' => 'streak',       'description' => '🔥 Seriya ko\'rish'],
    ['command' => 'achievements', 'description' => '🏅 Yutuqlar'],
    ['command' => 'help',         'description' => '❓ Yordam'],
    ['command' => 'cancel',       'description' => '❌ Bekor qilish'],
];

$result = $bot->setMyCommands($commands);
if ($result['ok'] ?? false) {
    echo "   ✅ " . count($commands) . " ta buyruq o'rnatildi\n";
} else {
    echo "   ❌ Buyruqlar xatosi: " . ($result['description'] ?? 'Noma\'lum') . "\n";
}

// ── 4. Bot info ────────────────────────────────────────────────────────────
echo "\n4️⃣  Bot ma'lumotlari:\n";
$me = $bot->getMe();
if ($me['ok'] ?? false) {
    $b = $me['result'];
    echo "   Username: @{$b['username']}\n";
    echo "   Name:     {$b['first_name']}\n";
    echo "   ID:       {$b['id']}\n";
}

echo "\n✅ Sozlash tugadi! Bot ishga tayyor.\n";
echo "🌐 Webhook: {$webhookUrl}\n\n";
