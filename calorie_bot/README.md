# 🥗 iCalCalorie Bot — PHP Telegram Kaloriya Hisoblagich

**@icalCalorie_bot kloni** — To'liq funksional Telegram bot, PHP 8.1+ va MySQL asosida qurilgan.

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Telegram Bot API](https://img.shields.io/badge/Telegram-Bot%20API-26A5E4?logo=telegram)](https://core.telegram.org/bots/api)

---

## 📱 Bot nima qila oladi?

| Funksiya | Tavsif |
|---|---|
| 🍽 **Kunlik taom** | Ertalik / tushlik / kechki / perекус bo'yicha kundalik yuritish |
| 🔍 **Taom qidirish** | 130+ taom bazasi, FULLTEXT + LIKE qidiruv |
| ✏️ **O'z taom** | Foydalanuvchi o'z shaxsiy taomlarini qo'shishi mumkin |
| 📸 **Rasm orqali** | Foto yuborib taomni aniqlash (OpenAI GPT-4o) |
| 💧 **Suv kuzatuvi** | Kunlik suv iste'molini qayd qilish |
| ⚖️ **Vazn kuzatuvi** | Vazn o'zgarishini kuzatish, sparkline grafik |
| 📊 **Statistika** | Bugun / hafta / oy / umumiy hisobot |
| 🎯 **Maqsad** | Kaloriya, oqsil, yog', karbohidrat kunlik me'yorlari |
| 🔥 **Seriya** | Ketma-ket faol kunlar streaki |
| 🏅 **Yutuqlar** | 17 ta achievement (streak, vazn, faollik) |
| ⚙️ **Sozlamalar** | Bildirishnomalar, til, kaloriya maqsadi, suv maqsadi |
| 👤 **Profil** | BMI hisoblash, TDEE, makrolar avtomatik sozlash |
| 🛡 **Admin** | Broadcast, foydalanuvchilar, taomlar boshqaruvi |

---

## 🏗 Texnologiyalar

```
PHP 8.1+   — Backend
MySQL 8.0+ — Ma'lumotlar bazasi  
Telegram Bot API — Webhook
Bootstrap 5.3 — Admin panel UI
Chart.js 4.4  — Admin grafiklar
OpenAI GPT-4o — Rasm orqali taom aniqlash (ixtiyoriy)
Eskiz.uz / Twilio — SMS (kelajakda)
```

---

## 📁 Loyiha tuzilmasi

```
calorie_bot/
├── admin/                  # Admin web panel (PHP)
│   ├── index.php           # Dashboard
│   ├── users.php           # Foydalanuvchilar
│   ├── diary.php           # Kundalik yozuvlar
│   ├── foods.php           # Taomlar CRUD
│   ├── broadcast.php       # Xabar yuborish
│   ├── login.php / logout.php
│   └── views/
│       └── layout_wrap.php # Umumiy HTML layout
│
├── database/
│   ├── migrations/         # 9 ta SQL migration
│   │   ├── 001_create_users_table.sql
│   │   ├── 002_create_foods_table.sql
│   │   ├── 003_create_diary_entries_table.sql
│   │   ├── 004_create_water_logs_table.sql
│   │   ├── 005_create_weight_logs_table.sql
│   │   ├── 006_create_user_states_table.sql
│   │   ├── 007_create_custom_foods_table.sql
│   │   ├── 008_create_achievements_table.sql
│   │   └── 009_create_streaks_table.sql
│   ├── migrate.php         # Migration runner
│   └── seed.php            # Foods + achievements seeder
│
├── public/
│   └── webhook.php         # Telegram webhook entry point
│
├── src/
│   ├── Core/
│   │   ├── Config.php      # .env configuration loader
│   │   ├── Database.php    # PDO wrapper (CRUD helpers)
│   │   ├── Helpers.php     # BMI, TDEE, progress bar, formatters
│   │   ├── Keyboard.php    # Inline & reply keyboard builder
│   │   ├── Logger.php      # File-based logger
│   │   ├── RateLimiter.php # Per-user rate limiting
│   │   ├── StateManager.php# Conversation state machine
│   │   └── TelegramBot.php # Telegram API client
│   │
│   ├── Features/
│   │   ├── AchievementChecker.php
│   │   ├── FoodDiary.php
│   │   ├── FoodSearch.php
│   │   ├── Statistics.php
│   │   ├── UserProfile.php
│   │   ├── WaterTracker.php
│   │   └── WeightTracker.php
│   │
│   └── Handlers/
│       ├── CallbackHandler.php   # Inline button callbacks
│       ├── CommandHandler.php    # /start /add /diary ...
│       └── MessageHandler.php   # Text + state machine
│
├── logs/                   # Bot logs (auto-created)
├── storage/                # Uploads (auto-created)
├── .env.example            # Environment template
├── composer.json
├── setup.php               # Webhook + commands setup
└── setup_webhook.sh        # curl-based webhook setup
```

---

## 🚀 O'rnatish

### 1. Talablar

- PHP 8.1+ (`curl`, `pdo`, `pdo_mysql`, `json`, `mbstring` extensions)
- MySQL 8.0+
- Composer
- HTTPS domain (Telegram webhook uchun majburiy)

### 2. Loyihani yuklab olish

```bash
git clone https://github.com/YOUR_USERNAME/calorie_bot.git
cd calorie_bot
```

### 3. Bog'liqliklarni o'rnatish

```bash
composer install
```

### 4. `.env` faylini sozlash

```bash
cp .env.example .env
nano .env  # yoki vim .env
```

Muhim sozlamalar:

```env
# Telegram
TELEGRAM_BOT_TOKEN=1234567890:AABBCCddeeff...    # BotFather dan olingan
TELEGRAM_WEBHOOK_SECRET=random_secret_string      # xavfsizlik uchun
TELEGRAM_BOT_USERNAME=your_bot_username
TELEGRAM_ADMIN_IDS=123456789                      # Sizning Telegram ID'ingiz

# Database
DB_HOST=127.0.0.1
DB_DATABASE=calorie_bot
DB_USERNAME=root
DB_PASSWORD=your_password

# Admin panel paroli
ADMIN_PASSWORD=SecureAdminPass123

# OpenAI (ixtiyoriy — rasm orqali taom aniqlash)
OPENAI_API_KEY=sk-...
```

### 5. Ma'lumotlar bazasini yaratish

```bash
php database/migrate.php
```

### 6. Taomlar va yutuqlarni qo'shish

```bash
php database/seed.php
```

### 7. Webhookni sozlash

```bash
# APP_URL = sizning HTTPS domeningiz
APP_URL=https://yourdomain.com php setup.php
```

Yoki `curl` bilan:

```bash
BOT_TOKEN=YOUR_TOKEN APP_URL=https://yourdomain.com bash setup_webhook.sh
```

### 8. Web server sozlash

**Nginx:**

```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;

    root /var/www/calorie_bot/public;
    index webhook.php;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # Webhook
    location = /webhook.php {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Admin panel
    location /admin/ {
        root /var/www/calorie_bot;
        try_files $uri $uri/ /admin/index.php?$query_string;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Logs himoyasi
    location ~ /logs/ { deny all; }
    location ~ /\.env  { deny all; }
}
```

**Apache `.htaccess`** (`public/` papkasida):

```apache
Options -Indexes
DirectoryIndex webhook.php

<Files .env>
    Require all denied
</Files>

RewriteEngine On
RewriteRule ^(.*)$ webhook.php [QSA,L]
```

---

## 💬 Bot buyruqlari

| Buyruq | Tavsif |
|---|---|
| `/start` | Botni ishga tushirish, yangi foydalanuvchi uchun onboarding |
| `/add [taom]` | Taom qo'shish (ixtiyoriy: `/add palov`) |
| `/diary [YYYY-MM-DD]` | Kunlik kundalik |
| `/water` | Suv qayd etish |
| `/weight` | Vazn kiritish |
| `/stats` | Statistika (bugun/hafta/oy/umumiy) |
| `/profile` | Profil va BMI |
| `/settings` | Sozlamalar (maqsad, bildirishnoma, til) |
| `/myfoods` | Shaxsiy taomlar ro'yxati |
| `/streak` | Ketma-ket kunlar seriyasi |
| `/achievements` | Yutuqlar ro'yxati |
| `/reset` | Profilni qayta sozlash |
| `/cancel` | Joriy amaliyotni bekor qilish |
| `/help` | Barcha buyruqlar |

---

## 🗄 Ma'lumotlar bazasi jadvallari

| Jadval | Tavsif |
|---|---|
| `users` | Foydalanuvchilar + profil + maqsadlar |
| `foods` | 130+ taom (kaloriya, makrolar, porsiya) |
| `diary_entries` | Kunlik taom yozuvlari |
| `water_logs` | Suv iste'mol logi |
| `weight_logs` | Vazn kuzatuvi tarixi |
| `user_states` | Conversation state machine |
| `custom_foods` | Foydalanuvchi qo'shgan taomlar |
| `achievements` | Yutuqlar ta'riflari |
| `user_achievements` | Kimga qaysi yutuq berilgani |
| `user_streaks` | Seriya (streak) hisoblagich |

---

## 🔒 Xavfsizlik

- ✅ Webhook `X-Telegram-Bot-Api-Secret-Token` header orqali verifikatsiya
- ✅ Per-user rate limiting (30 so'rov/daqiqa)
- ✅ Admin panel parol himoyasi (session)
- ✅ PDO prepared statements (SQL injection yo'q)
- ✅ `htmlspecialchars()` — XSS himoyasi
- ✅ `.env` va `logs/` papkasi veb orqali blok qilingan
- ✅ Admin panel faqat ADMIN_IDS uchun

---

## 🏅 Yutuqlar tizimi

| Yutuq | Shart |
|---|---|
| 🔥 Uch kun | 3 kun ketma-ket |
| 🔥 Bir hafta | 7 kun ketma-ket |
| ⚡ Ikki hafta | 14 kun ketma-ket |
| 💫 Bir oy | 30 kun ketma-ket |
| 👑 100 kun | 100 kun ketma-ket |
| 🌱 Boshlandi | 7 kun faol |
| 🌿 Bir oylik | 30 kun faol |
| 📝 Birinchi o'nta | 10 yozuv |
| 📊 50 yozuv | 50 yozuv |
| 🏆 500 yozuv | 500 yozuv |
| ⚖️ -1 кг | 1 kg yo'qotildi |
| 🎯 -5 кг | 5 kg yo'qotildi |
| 🌟 -10 кг | 10 kg yo'qotildi |

---

## 🤖 OpenAI rasm integratsiyasi (ixtiyoriy)

`OPENAI_API_KEY` sozlanganda:

1. Foydalanuvchi taom rasmi yuboradi
2. Bot GPT-4o Vision API'ga yuboradi
3. AI taom nomini, kaloriya va makrolarni aniqlaydi
4. Foydalanuvchi tasdiqlab kundalikka qo'shadi

---

## 🛡 Admin panel

URL: `https://yourdomain.com/admin/`

| Sahifa | Tavsif |
|---|---|
| Dashboard | Statistika, grafiklar, top taomlar, yangi foydalanuvchilar |
| Foydalanuvchilar | Ro'yxat, bloklash, o'chirish |
| Kundalik | Barcha yozuvlar, filter (sana, user) |
| Taomlar | CRUD, kategoriya filter, tasdiqlash |
| Broadcast | Barcha / faol / sozlagan foydalanuvchilarga xabar |

---

## ⚙️ Muhit o'zgaruvchilari (to'liq)

```env
APP_NAME="iCalCalorie Bot"
APP_URL=https://yourdomain.com
APP_DEBUG=false
LOG_LEVEL=info                 # debug | info | warning | error

TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=       # ixtiyoriy, lekin tavsiya etiladi
TELEGRAM_ADMIN_IDS=            # vergul bilan ajratilgan Telegram ID'lar

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=calorie_bot
DB_USERNAME=root
DB_PASSWORD=

ADMIN_PASSWORD=SecurePass123   # Admin panel paroli

OPENAI_API_KEY=                # ixtiyoriy
OPENAI_MODEL=gpt-4o            # ixtiyoriy

RATE_LIMIT_PER_MINUTE=30
MAX_DAILY_FOOD_ENTRIES=100
```

---

## 🐛 Nosozliklarni bartaraf qilish

**Bot javob bermaydi:**
```bash
# Webhook holatini tekshirish
curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo | python3 -m json.tool

# Log faylini ko'rish
tail -f logs/bot-$(date +%Y-%m-%d).log
```

**Database xatosi:**
```bash
# Migratsiyalarni qayta ishlatish
php database/migrate.php
```

**Webhook 403:**
- `.env` faylidagi `TELEGRAM_WEBHOOK_SECRET` va server tokenini moslashtiring

---

## 📝 Litsenziya

MIT License — erkin foydalaning, o'zgartiring va tarqating.

---

## 🙏 Minnatdorchilik

- [Telegram Bot API](https://core.telegram.org/bots/api)
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
- [OpenAI API](https://platform.openai.com/docs)
- [Bootstrap 5](https://getbootstrap.com)
- [Chart.js](https://chartjs.org)
