# 🏟️ O'ynaa - Sport maydonlarini bron qilish platformasi

**Oynaa** - O'zbekistondagi sport maydonlarini (futbol, tennis, basketbol, badminton va boshqalar) onlayn bron qilish platformasi.

---

## 📁 Loyiha tuzilmasi

```
polyabron/
├── api/          # Laravel REST API backend
├── dashboard/    # Laravel Admin Panel (Blade)
└── flutter_app/  # Flutter Mobile App (Android & iOS)
```

---

## 🔧 Texnologiyalar

| Qatlam | Texnologiya |
|--------|------------|
| Mobile | Flutter 3.x + BLoC |
| API | Laravel 11 + JWT Auth |
| Dashboard | Laravel 11 + Bootstrap 5.3 |
| DB | MySQL |
| Maps | Google Maps Flutter |
| SMS | Eskiz.uz |
| To'lov | Payme + Click |
| Push | Firebase FCM |

---

## 🚀 O'rnatish

### 1. Laravel API (api/)

```bash
cd api
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan storage:link
php artisan serve --port=8000
```

### 2. Laravel Dashboard (dashboard/)

```bash
cd dashboard
cp .env.example .env
# .env ichida DB malumotlarini kiriting (api bilan bir xil DB)
composer install
php artisan key:generate
php artisan db:seed
php artisan serve --port=8001
```

**Admin kirish:**
- URL: `http://localhost:8001`
- Email: `admin@oynaa.uz`
- Parol: `Admin@123`

### 3. Flutter App (flutter_app/)

```bash
cd flutter_app
flutter pub get
# lib/core/constants/app_constants.dart ichida API URL va Google Maps kalitini kiriting
flutter run
```

---

## ⚙️ Konfiguratsiya

### `.env` faylida to'ldirish kerak:

```env
# Google Maps
GOOGLE_MAPS_API_KEY=your_key_here

# Eskiz SMS
ESKIZ_EMAIL=your@email.com
ESKIZ_PASSWORD=your_password

# Payme
PAYME_MERCHANT_ID=your_merchant_id
PAYME_SECRET_KEY=your_secret_key
PAYME_TEST_SECRET_KEY=your_test_key
PAYME_ENVIRONMENT=test  # prod uchun: production

# Click
CLICK_SERVICE_ID=your_service_id
CLICK_MERCHANT_ID=your_merchant_id
CLICK_SECRET_KEY=your_secret_key
CLICK_MERCHANT_USER_ID=your_user_id
```

---

## 📱 Flutter — Flutter `app_constants.dart` sozlash

```dart
static const String baseUrl = 'https://your-api-url.com/api/v1';
static const String googleMapsApiKey = 'YOUR_GOOGLE_MAPS_API_KEY';
```

---

## 🗄️ Database Jadvallar

| Jadval | Tavsif |
|--------|--------|
| `users` | Foydalanuvchilar (user, owner, admin) |
| `categories` | Sport turlari (futbol, tennis...) |
| `venues` | Maydonlar |
| `bookings` | Bronlar |
| `payments` | To'lovlar (Payme, Click) |
| `reviews` | Izohlar va reytinglar |
| `favorites` | Sevimlilar |
| `notifications` | Bildirishnomalar |
| `banners` | Bosh sahifa bannerlari |
| `sms_verifications` | OTP kodlar |
| `time_slots` | Vaqt slotlari |

---

## 🔗 API Endpointlar

### Auth
```
POST /api/v1/auth/send-otp      # OTP yuborish
POST /api/v1/auth/verify-otp    # OTP tasdiqlash
POST /api/v1/auth/register      # Ro'yxatdan o'tish
POST /api/v1/auth/login         # Kirish (parol)
POST /api/v1/auth/login-otp     # Kirish (OTP)
POST /api/v1/auth/logout        # Chiqish
GET  /api/v1/auth/me            # Profil
```

### Maydonlar
```
GET  /api/v1/home                        # Bosh sahifa
GET  /api/v1/venues                      # Ro'yxat (filter)
GET  /api/v1/venues/nearby?lat=&lng=     # Yaqin
GET  /api/v1/venues/{id}                 # Detail
GET  /api/v1/venues/{id}/availability    # Vaqtlar
GET  /api/v1/venues/{id}/reviews         # Izohlar
```

### Bronlar
```
GET  /api/v1/bookings              # Mening bronlarim
POST /api/v1/bookings              # Yangi bron
GET  /api/v1/bookings/{id}         # Detail
POST /api/v1/bookings/{id}/cancel  # Bekor qilish
```

### To'lov
```
GET  /api/v1/payment/payme/url/{id}   # Payme URL
POST /api/v1/payment/payme/merchant   # Payme webhook
GET  /api/v1/payment/click/url/{id}   # Click URL
POST /api/v1/payment/click/prepare    # Click prepare
POST /api/v1/payment/click/complete   # Click complete
```

---

## 📦 Flutter Ekranlar

| Ekran | Tavsif |
|-------|--------|
| Splash | Yuklash va auth tekshiruv |
| Onboarding | Tanishuv slaydlari (3 ta) |
| Phone | Telefon kiritish |
| OTP | 4 xonali kod kiritish |
| Register | Ro'yxat shakli |
| Home | Bannerlar, kategoriyalar, tavsiyalar |
| Venues List | Filter, sort, pagination |
| Venue Detail | Rasm, info, narx, bron tugmasi |
| Venue Map | Google Maps + marker + yaqin maydonlar |
| Booking | Sana + vaqt tanlash + bron yaratish |
| Booking List | Tab: kelayotgan / yakunlangan / bekor |
| Booking Detail | Bron ma'lumotlari + bekor qilish |
| Payment | Payme / Click / Naqd tanlash |
| Payment Success | Muvaffaqiyat ekrani |
| Profile | Profil, sevimlilar, bildirishnomalar |
| Favorites | Sevimli maydonlar |
| Notifications | Bildirishnomalar ro'yxati |
| Edit Profile | Profil tahrirlash |

---

## 🛡️ Admin Dashboard Funksiyalar

- 📊 Dashboard (statistika, grafiklar)
- 🏟️ Maydonlar boshqaruvi (tasdiqlash, rad etish, featured)
- 📅 Bronlar ro'yxati va holat o'zgartirish
- 👥 Foydalanuvchilar (bloklash, rol o'zgartirish)
- 💳 To'lovlar (Payme, Click statistika)
- ⭐ Izohlar moderatsiya
- 🏷️ Kategoriyalar CRUD
- 🖼️ Bannerlar CRUD
- 📤 CSV Export

---

## 📞 Integratsiyalar

### Eskiz SMS (Uzbekiston)
> [eskiz.uz](https://eskiz.uz) — API orqali SMS yuborish

### Payme
> [paycom.uz](https://paycom.uz) — Merchant API, test/prod muhit

### Click
> [click.uz](https://click.uz) — Prepare + Complete webhook

### Google Maps
> [Google Cloud Console](https://console.cloud.google.com) — Maps SDK for Android/iOS

---

## 📝 Litsenziya
MIT License
