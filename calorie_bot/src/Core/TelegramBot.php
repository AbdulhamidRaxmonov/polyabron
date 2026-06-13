<?php

namespace App\Core;

class TelegramBot
{
    private static string $apiBase = 'https://api.telegram.org/bot';
    private string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? Config::get('bot_token');
    }

    // ── Sending ───────────────────────────────────────────────────────────

    public function sendMessage(
        int|string $chatId,
        string     $text,
        array      $extra = []
    ): ?array {
        return $this->call('sendMessage', array_merge([
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ], $extra));
    }

    public function editMessageText(
        int|string $chatId,
        int        $messageId,
        string     $text,
        array      $extra = []
    ): ?array {
        return $this->call('editMessageText', array_merge([
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ], $extra));
    }

    public function deleteMessage(int|string $chatId, int $messageId): ?array
    {
        return $this->call('deleteMessage', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): ?array
    {
        return $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
            'show_alert'        => $showAlert,
        ]);
    }

    public function sendPhoto(int|string $chatId, string $photoUrl, string $caption = '', array $extra = []): ?array
    {
        return $this->call('sendPhoto', array_merge([
            'chat_id'    => $chatId,
            'photo'      => $photoUrl,
            'caption'    => $caption,
            'parse_mode' => 'HTML',
        ], $extra));
    }

    public function sendChatAction(int|string $chatId, string $action = 'typing'): ?array
    {
        return $this->call('sendChatAction', ['chat_id' => $chatId, 'action' => $action]);
    }

    public function forwardMessage(int|string $toChatId, int|string $fromChatId, int $messageId): ?array
    {
        return $this->call('forwardMessage', [
            'chat_id'      => $toChatId,
            'from_chat_id' => $fromChatId,
            'message_id'   => $messageId,
        ]);
    }

    // ── Bot setup ──────────────────────────────────────────────────────────

    public function setWebhook(string $url, string $secret = ''): ?array
    {
        return $this->call('setWebhook', array_filter([
            'url'            => $url,
            'secret_token'   => $secret ?: null,
            'max_connections' => 40,
            'allowed_updates' => ['message', 'callback_query', 'my_chat_member'],
        ]));
    }

    public function deleteWebhook(): ?array
    {
        return $this->call('deleteWebhook', ['drop_pending_updates' => true]);
    }

    public function getWebhookInfo(): ?array
    {
        return $this->call('getWebhookInfo', []);
    }

    public function setMyCommands(array $commands, string $scope = 'default'): ?array
    {
        return $this->call('setMyCommands', [
            'commands' => $commands,
            'scope'    => ['type' => $scope],
        ]);
    }

    public function getMe(): ?array
    {
        return $this->call('getMe', []);
    }

    // ── Core HTTP ──────────────────────────────────────────────────────────

    public function call(string $method, array $params): ?array
    {
        $url = self::$apiBase . $this->token . '/' . $method;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($params),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            Logger::error("cURL error calling $method: $curlErr");
            return null;
        }

        $data = json_decode($response, true);

        if (!($data['ok'] ?? false)) {
            Logger::warning("Telegram API error on $method", [
                'code'        => $data['error_code'] ?? $httpCode,
                'description' => $data['description'] ?? 'unknown',
                'params'      => array_keys($params),
            ]);
        }

        return $data;
    }

    // ── Update parsing ─────────────────────────────────────────────────────

    public static function getUpdate(): ?array
    {
        $body = file_get_contents('php://input');
        if (empty($body)) return null;

        $update = json_decode($body, true);
        return is_array($update) ? $update : null;
    }

    public static function getChatId(array $update): int
    {
        if (isset($update['message']['chat']['id']))          return (int) $update['message']['chat']['id'];
        if (isset($update['callback_query']['message']['chat']['id'])) return (int) $update['callback_query']['message']['chat']['id'];
        return 0;
    }

    public static function getUserId(array $update): int
    {
        if (isset($update['message']['from']['id']))          return (int) $update['message']['from']['id'];
        if (isset($update['callback_query']['from']['id']))   return (int) $update['callback_query']['from']['id'];
        return 0;
    }

    public static function getFrom(array $update): ?array
    {
        return $update['message']['from'] ?? $update['callback_query']['from'] ?? null;
    }

    public static function getText(array $update): string
    {
        return $update['message']['text'] ?? '';
    }

    public static function getCallbackData(array $update): string
    {
        return $update['callback_query']['data'] ?? '';
    }

    public static function getCallbackQueryId(array $update): string
    {
        return $update['callback_query']['id'] ?? '';
    }

    public static function getMessageId(array $update): int
    {
        return (int)($update['message']['message_id']
            ?? $update['callback_query']['message']['message_id']
            ?? 0);
    }

    public static function getPhoto(array $update): ?array
    {
        $photos = $update['message']['photo'] ?? null;
        if (!$photos) return null;
        return end($photos); // largest size
    }

    public static function getFileId(array $update): ?string
    {
        $photo = self::getPhoto($update);
        return $photo['file_id'] ?? null;
    }

    public function getFileUrl(string $fileId): ?string
    {
        $result = $this->call('getFile', ['file_id' => $fileId]);
        if (!($result['ok'] ?? false)) return null;
        $path = $result['result']['file_path'] ?? null;
        if (!$path) return null;
        return "https://api.telegram.org/file/bot{$this->token}/{$path}";
    }
}
