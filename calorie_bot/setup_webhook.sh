#!/bin/bash
# Quick webhook setup via curl (without PHP)
# Usage: BOT_TOKEN=xxx APP_URL=https://domain.com bash setup_webhook.sh

BOT_TOKEN="${BOT_TOKEN:-YOUR_BOT_TOKEN}"
APP_URL="${APP_URL:-https://yourdomain.com}"
SECRET="${WEBHOOK_SECRET:-}"

echo "Setting webhook..."
curl -sS "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook" \
  -d "url=${APP_URL}/webhook.php" \
  -d "secret_token=${SECRET}" \
  -d "allowed_updates=[\"message\",\"callback_query\"]" | python3 -m json.tool

echo ""
echo "Done! Webhook set to: ${APP_URL}/webhook.php"
