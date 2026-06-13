<?php

namespace App\Services;

use App\Models\SmsVerification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SmsService
{
    private string $baseUrl = 'https://notify.eskiz.uz/api';
    private ?string $token = null;

    public function getToken(): ?string
    {
        try {
            $response = Http::post("{$this->baseUrl}/auth/login", [
                'email'    => config('services.eskiz.email'),
                'password' => config('services.eskiz.password'),
            ]);

            if ($response->successful()) {
                $this->token = $response->json('data.token');
                return $this->token;
            }
        } catch (\Exception $e) {
            Log::error('Eskiz auth error: ' . $e->getMessage());
        }
        return null;
    }

    public function sendSms(string $phone, string $message): bool
    {
        try {
            $token = $this->getToken();
            if (!$token) return false;

            // Normalize phone: +998901234567 -> 998901234567
            $phone = preg_replace('/[^0-9]/', '', $phone);

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/message/sms/send", [
                    'mobile_phone' => $phone,
                    'message'      => $message,
                    'from'         => config('services.eskiz.from', '4546'),
                    'callback_url' => null,
                ]);

            if ($response->successful()) {
                Log::info("SMS sent to {$phone}");
                return true;
            }

            Log::error("SMS send failed: " . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('SMS error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendOtp(string $phone, string $type = 'register'): string
    {
        // Delete old codes
        SmsVerification::where('phone', $phone)
            ->where('type', $type)
            ->where('is_used', false)
            ->delete();

        $code = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        SmsVerification::create([
            'phone'      => $phone,
            'code'       => $code,
            'type'       => $type,
            'is_used'    => false,
            'attempts'   => 0,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $message = "O'ynaa tasdiqlash kodi: {$code}\nUshbu kodni hech kimga bermang.";
        $this->sendSms($phone, $message);

        return $code;
    }

    public function verifyOtp(string $phone, string $code, string $type = 'register'): bool
    {
        $verification = SmsVerification::where('phone', $phone)
            ->where('type', $type)
            ->where('is_used', false)
            ->latest()
            ->first();

        if (!$verification) return false;

        // Increment attempts
        $verification->increment('attempts');

        if (!$verification->isValid($code)) {
            return false;
        }

        $verification->update(['is_used' => true]);
        return true;
    }
}
