<?php
/**
 * Telegram Webhook Entry Point
 * URL: https://yourdomain.com/webhook.php
 */

declare(strict_types=1);

// ── Bootstrap ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\{Config, Logger, RateLimiter, TelegramBot};
use App\Features\{UserProfile, AchievementChecker};
use App\Handlers\{CommandHandler, CallbackHandler, MessageHandler};

Config::load();

// ── Security: verify Telegram secret token ────────────────────────────────
$secret = Config::get('webhook_secret');
if ($secret) {
    $header = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals($secret, $header)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Parse update ──────────────────────────────────────────────────────────
$update = TelegramBot::getUpdate();

if (!$update) {
    http_response_code(200);
    exit;
}

// Always return 200 immediately to Telegram
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['ok' => true]);

// Flush output so Telegram doesn't wait
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ob_end_flush();
    flush();
}

// ── Process update ────────────────────────────────────────────────────────
try {
    $bot      = new TelegramBot();
    $userId   = TelegramBot::getUserId($update);
    $chatId   = TelegramBot::getChatId($update);

    if (!$userId || !$chatId) exit;

    // Rate limiter
    if (!RateLimiter::check($userId)) {
        Logger::warning("Rate limited user $userId");
        exit;
    }

    $from = TelegramBot::getFrom($update);
    if (!$from) exit;

    // Register / update user
    $user = UserProfile::findByTelegramId($userId);
    if (!$user) {
        $user = UserProfile::createFromTelegram($from);
        Logger::info("New user registered", ['telegram_id' => $userId]);
    } else {
        // Update username/name if changed
        $updates = [];
        if (($from['username'] ?? null) !== ($user['username'] ?? null)) {
            $updates['username'] = $from['username'] ?? null;
        }
        if (($from['first_name'] ?? '') !== ($user['first_name'] ?? '')) {
            $updates['first_name'] = $from['first_name'] ?? '';
        }
        if ($updates) {
            \App\Core\Database::update('users', $updates, ['telegram_id' => $userId]);
            $user = array_merge($user, $updates);
        }
    }

    // Block check
    if ($user['is_blocked'] ?? false) exit;

    // ── Route update ────────────────────────────────────────────────────

    // Callback query
    if (isset($update['callback_query'])) {
        $data    = TelegramBot::getCallbackData($update);
        $handler = new CallbackHandler($bot, $update, $user);
        $handler->handle($data);

        // Check achievements after callback
        checkAndNotifyAchievements($bot, $user, $userId, $chatId);
        exit;
    }

    // Regular message
    if (isset($update['message'])) {
        $text    = TelegramBot::getText($update);
        $msgId   = TelegramBot::getMessageId($update);

        // Photo message (food image recognition)
        $fileId = TelegramBot::getFileId($update);
        if ($fileId) {
            handlePhotoMessage($bot, $update, $user, $fileId, $chatId);
            exit;
        }

        // Empty text
        if (trim($text) === '') exit;

        // Commands
        if (str_starts_with($text, '/')) {
            $parts   = explode(' ', $text, 2);
            $command = strtolower(explode('@', $parts[0])[0]); // remove @botname
            $args    = $parts[1] ?? '';

            $handler = new CommandHandler($bot, $update, $user);
            $handler->handle($command, $args);

            // Check achievements
            checkAndNotifyAchievements($bot, $user, $userId, $chatId);
            exit;
        }

        // Text message (state machine / menu buttons)
        $bot->sendChatAction($chatId, 'typing');
        $handler = new MessageHandler($bot, $update, $user);
        $handler->handle($text);

        // Check achievements
        checkAndNotifyAchievements($bot, $user, $userId, $chatId);
    }

} catch (\Throwable $e) {
    Logger::exception($e);
}

// ═════════════════════════════════════════════════════════════════════════
// Helpers
// ═════════════════════════════════════════════════════════════════════════

/**
 * Check for newly earned achievements and notify user.
 */
function checkAndNotifyAchievements(TelegramBot $bot, array $user, int $userId, int $chatId): void
{
    try {
        $checker = new AchievementChecker((int)$user['id']);
        $earned  = $checker->check();

        foreach ($earned as $ach) {
            $bot->sendMessage($chatId,
                "🎉 <b>Yangi yutuq!</b>\n\n"
                . "{$ach['icon']} <b>{$ach['title']}</b>\n"
                . "{$ach['description']}"
            );
        }
    } catch (\Throwable $e) {
        Logger::error("Achievement check failed: " . $e->getMessage());
    }
}

/**
 * Handle photo messages — food recognition (optional OpenAI).
 */
function handlePhotoMessage(TelegramBot $bot, array $update, array $user, string $fileId, int $chatId): void
{
    $openaiKey = \App\Core\Config::get('openai_key');

    if (!$openaiKey) {
        $bot->sendMessage($chatId,
            "📸 Rasm qabul qilindi!\n\n"
            . "⚠️ Rasm orqali taom aniqlash hozircha yoqilmagan.\n"
            . "Taom nomini yozing yoki /add buyrug'ini ishlating."
        );
        return;
    }

    $bot->sendChatAction($chatId, 'typing');

    try {
        $fileUrl = $bot->getFileUrl($fileId);
        if (!$fileUrl) throw new \Exception('File URL olishda xato');

        $imageData = base64_encode(file_get_contents($fileUrl));
        $mimeType  = 'image/jpeg';

        $payload = [
            'model' => \App\Core\Config::get('openai_model', 'gpt-4o'),
            'messages' => [[
                'role'    => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Bu rasmda qanday taom bor? Taxminiy miqdori bilan 100g uchun kaloriya, oqsil (g), yog\' (g), karbohidrat (g) ni JSON formatda ber. Format: {"name":"taom nomi","calories":0,"protein":0,"fat":0,"carbs":0,"amount_g":100}. Faqat JSON qaytarsin, boshqa narsa yozmasin.',
                    ],
                    [
                        'type'      => 'image_url',
                        'image_url' => ['url' => "data:{$mimeType};base64,{$imageData}"],
                    ],
                ],
            ]],
            'max_tokens' => 300,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $openaiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data    = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Extract JSON from response
        if (preg_match('/\{.*\}/s', $content, $m)) {
            $food = json_decode($m[0], true);

            if ($food && isset($food['name'])) {
                \App\Core\StateManager::set((int)$user['telegram_id'], 'add_food:amount', 'confirm', [
                    'food_name'  => $food['name'],
                    'calories'   => $food['calories']  ?? 0,
                    'protein_g'  => $food['protein']   ?? 0,
                    'fat_g'      => $food['fat']        ?? 0,
                    'carbs_g'    => $food['carbs']      ?? 0,
                    'amount_g'   => $food['amount_g']  ?? 100,
                    'entry_date' => date('Y-m-d'),
                    'ai_source'  => true,
                ]);

                $bot->sendMessage($chatId,
                    "🤖 <b>AI aniqladi:</b> {$food['name']}\n\n"
                    . "🔥 ~{$food['calories']} ккал/100г\n"
                    . "🥩 Oqsil: ~{$food['protein']}г | 🧈 Yog': ~{$food['fat']}г | 🍞 Karbo: ~{$food['carbs']}г\n\n"
                    . "⚠️ <i>Bu taxminiy qiymat. Tekshirib ko'ring.</i>\n\n"
                    . "Qaysi ovqatga qo'shmoqchisiz?",
                    ['reply_markup' => \App\Core\Keyboard::mealType()]
                );
                return;
            }
        }

        $bot->sendMessage($chatId,
            "😔 Rasmdan taomni aniqlab bo'lmadi.\n\nIltimos, taom nomini yozing."
        );

    } catch (\Throwable $e) {
        Logger::error("Photo recognition error: " . $e->getMessage());
        $bot->sendMessage($chatId,
            "❌ Rasmni qayta ishlashda xato yuz berdi.\n\nTaom nomini yozing yoki /add ishlating."
        );
    }
}
