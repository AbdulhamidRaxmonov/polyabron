<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymeService
{
    // Payme error codes
    const ERROR_INVALID_JSON       = -32700;
    const ERROR_METHOD_NOT_FOUND   = -32601;
    const ERROR_INVALID_PARAMS     = -32600;
    const ERROR_TRANSACTION_NOT_FOUND = -31003;
    const ERROR_INVALID_AMOUNT     = -31001;
    const ERROR_TRANSACTION_DONE   = -31008;
    const ERROR_CANCEL_TRANSACTION = -31007;

    // Transaction states
    const STATE_CREATED   = 1;
    const STATE_COMPLETED = 2;
    const STATE_CANCELLED = -1;
    const STATE_CANCELLED_AFTER_COMPLETE = -2;

    private string $merchantId;
    private string $secretKey;
    private bool   $isTest;

    public function __construct()
    {
        $this->merchantId = config('services.payme.merchant_id');
        $this->secretKey  = config('services.payme.environment') === 'test'
            ? config('services.payme.test_secret_key')
            : config('services.payme.secret_key');
        $this->isTest = config('services.payme.environment') === 'test';
    }

    /**
     * Generate checkout URL for Payme
     */
    public function generateCheckoutUrl(Booking $booking): string
    {
        $params = [
            'm'  => $this->merchantId,
            'ac' => ['booking_id' => $booking->id],
            'a'  => (int)($booking->final_amount * 100), // tiyin
            'l'  => 'uz',
        ];

        $encoded = base64_encode(json_encode($params));
        $baseUrl = $this->isTest
            ? config('services.payme.test_url', 'https://test.paycom.uz')
            : config('services.payme.url', 'https://checkout.paycom.uz');

        return "{$baseUrl}/{$encoded}";
    }

    /**
     * Verify Payme merchant key from request
     */
    public function verifyAuth(string $authorization): bool
    {
        $credentials = base64_decode(str_replace('Basic ', '', $authorization));
        [, $password] = explode(':', $credentials, 2);
        return $password === $this->secretKey;
    }

    /**
     * Handle Payme merchant API methods
     */
    public function handle(array $request, string $authorization): array
    {
        if (!$this->verifyAuth($authorization)) {
            return $this->errorResponse(
                $request['id'] ?? null,
                self::ERROR_INVALID_PARAMS,
                ['ru' => 'Неверный авторизационный токен', 'uz' => 'Noto\'g\'ri token']
            );
        }

        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id     = $request['id'] ?? null;

        return match ($method) {
            'CheckPerformTransaction'  => $this->checkPerformTransaction($params, $id),
            'CreateTransaction'        => $this->createTransaction($params, $id),
            'PerformTransaction'       => $this->performTransaction($params, $id),
            'CancelTransaction'        => $this->cancelTransaction($params, $id),
            'CheckTransaction'         => $this->checkTransaction($params, $id),
            'GetStatement'             => $this->getStatement($params, $id),
            default                    => $this->errorResponse($id, self::ERROR_METHOD_NOT_FOUND,
                ['ru' => 'Метод не найден', 'uz' => 'Metod topilmadi'])
        };
    }

    private function checkPerformTransaction(array $params, $id): array
    {
        $bookingId = $params['account']['booking_id'] ?? null;
        $amount    = $params['amount'] ?? 0;

        $booking = Booking::find($bookingId);

        if (!$booking) {
            return $this->errorResponse($id, self::ERROR_INVALID_PARAMS,
                ['ru' => 'Заказ не найден', 'uz' => 'Bron topilmadi']);
        }

        $expectedAmount = (int)($booking->final_amount * 100);
        if ($amount !== $expectedAmount) {
            return $this->errorResponse($id, self::ERROR_INVALID_AMOUNT,
                ['ru' => 'Неверная сумма', 'uz' => 'Noto\'g\'ri summa']);
        }

        return ['id' => $id, 'result' => ['allow' => true]];
    }

    private function createTransaction(array $params, $id): array
    {
        $bookingId = $params['account']['booking_id'] ?? null;
        $transId   = $params['id'];
        $amount    = $params['amount'];
        $time      = $params['time'];

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return $this->errorResponse($id, self::ERROR_INVALID_PARAMS,
                ['ru' => 'Заказ не найден', 'uz' => 'Bron topilmadi']);
        }

        // Check existing payment
        $payment = Payment::where('provider_transaction_id', $transId)
            ->where('provider', 'payme')
            ->first();

        if ($payment) {
            if ($payment->status !== 'pending') {
                return $this->errorResponse($id, self::ERROR_TRANSACTION_DONE,
                    ['ru' => 'Транзакция уже завершена', 'uz' => 'Tranzaksiya allaqachon yakunlangan']);
            }

            return ['id' => $id, 'result' => [
                'create_time' => $payment->created_at->timestamp * 1000,
                'transaction' => (string)$payment->id,
                'state'       => self::STATE_CREATED,
            ]];
        }

        // Create new payment
        $payment = Payment::create([
            'user_id'                => $booking->user_id,
            'booking_id'             => $booking->id,
            'provider_transaction_id' => $transId,
            'provider'               => 'payme',
            'amount'                 => $amount / 100,
            'currency'               => 'UZS',
            'status'                 => 'pending',
            'provider_response'      => $params,
        ]);

        return ['id' => $id, 'result' => [
            'create_time' => $payment->created_at->timestamp * 1000,
            'transaction' => (string)$payment->id,
            'state'       => self::STATE_CREATED,
        ]];
    }

    private function performTransaction(array $params, $id): array
    {
        $transId = $params['id'];

        $payment = Payment::where('provider_transaction_id', $transId)
            ->where('provider', 'payme')
            ->first();

        if (!$payment) {
            return $this->errorResponse($id, self::ERROR_TRANSACTION_NOT_FOUND,
                ['ru' => 'Транзакция не найдена', 'uz' => 'Tranzaksiya topilmadi']);
        }

        if ($payment->status === 'completed') {
            return ['id' => $id, 'result' => [
                'transaction'  => (string)$payment->id,
                'perform_time' => $payment->paid_at->timestamp * 1000,
                'state'        => self::STATE_COMPLETED,
            ]];
        }

        if ($payment->status !== 'pending') {
            return $this->errorResponse($id, self::ERROR_TRANSACTION_DONE,
                ['ru' => 'Транзакция не может быть выполнена', 'uz' => 'Tranzaksiya bajarilmaydi']);
        }

        $now = now();
        $payment->update([
            'status'  => 'completed',
            'paid_at' => $now,
        ]);

        // Update booking
        if ($payment->booking) {
            $payment->booking->update([
                'payment_status' => 'paid',
                'payment_method' => 'payme',
                'status'         => 'confirmed',
                'confirmed_at'   => $now,
                'transaction_id' => $transId,
            ]);
        }

        return ['id' => $id, 'result' => [
            'transaction'  => (string)$payment->id,
            'perform_time' => $now->timestamp * 1000,
            'state'        => self::STATE_COMPLETED,
        ]];
    }

    private function cancelTransaction(array $params, $id): array
    {
        $transId = $params['id'];
        $reason  = $params['reason'] ?? 0;

        $payment = Payment::where('provider_transaction_id', $transId)
            ->where('provider', 'payme')
            ->first();

        if (!$payment) {
            return $this->errorResponse($id, self::ERROR_TRANSACTION_NOT_FOUND,
                ['ru' => 'Транзакция не найдена', 'uz' => 'Tranzaksiya topilmadi']);
        }

        $state = $payment->status === 'completed'
            ? self::STATE_CANCELLED_AFTER_COMPLETE
            : self::STATE_CANCELLED;

        $payment->update(['status' => 'cancelled']);

        if ($payment->booking) {
            $payment->booking->update([
                'status'        => 'cancelled',
                'payment_status' => 'refunded',
                'cancelled_at'  => now(),
                'cancel_reason' => "Payme cancel reason: {$reason}",
            ]);
        }

        return ['id' => $id, 'result' => [
            'transaction'  => (string)$payment->id,
            'cancel_time'  => now()->timestamp * 1000,
            'state'        => $state,
        ]];
    }

    private function checkTransaction(array $params, $id): array
    {
        $transId = $params['id'];

        $payment = Payment::where('provider_transaction_id', $transId)
            ->where('provider', 'payme')
            ->first();

        if (!$payment) {
            return $this->errorResponse($id, self::ERROR_TRANSACTION_NOT_FOUND,
                ['ru' => 'Транзакция не найдена', 'uz' => 'Tranzaksiya topilmadi']);
        }

        $state = match ($payment->status) {
            'pending'   => self::STATE_CREATED,
            'completed' => self::STATE_COMPLETED,
            'cancelled' => self::STATE_CANCELLED,
            default     => self::STATE_CANCELLED,
        };

        return ['id' => $id, 'result' => [
            'create_time'  => $payment->created_at->timestamp * 1000,
            'perform_time' => $payment->paid_at ? $payment->paid_at->timestamp * 1000 : 0,
            'cancel_time'  => 0,
            'transaction'  => (string)$payment->id,
            'state'        => $state,
            'reason'       => null,
        ]];
    }

    private function getStatement(array $params, $id): array
    {
        $from = $params['from'] / 1000;
        $to   = $params['to'] / 1000;

        $payments = Payment::where('provider', 'payme')
            ->whereBetween('created_at', [
                date('Y-m-d H:i:s', $from),
                date('Y-m-d H:i:s', $to),
            ])
            ->whereIn('status', ['completed', 'cancelled'])
            ->get();

        $transactions = $payments->map(function ($p) {
            return [
                'id'           => $p->provider_transaction_id,
                'time'         => $p->created_at->timestamp * 1000,
                'amount'       => (int)($p->amount * 100),
                'account'      => ['booking_id' => $p->booking_id],
                'create_time'  => $p->created_at->timestamp * 1000,
                'perform_time' => $p->paid_at ? $p->paid_at->timestamp * 1000 : 0,
                'cancel_time'  => 0,
                'transaction'  => (string)$p->id,
                'state'        => $p->status === 'completed' ? self::STATE_COMPLETED : self::STATE_CANCELLED,
                'reason'       => null,
                'receivers'    => null,
            ];
        });

        return ['id' => $id, 'result' => ['transactions' => $transactions]];
    }

    private function errorResponse($id, int $code, array $message): array
    {
        return [
            'id'    => $id,
            'error' => [
                'code'    => $code,
                'message' => $message,
                'data'    => null,
            ],
        ];
    }
}
