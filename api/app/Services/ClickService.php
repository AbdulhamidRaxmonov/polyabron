<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class ClickService
{
    private int    $serviceId;
    private int    $merchantId;
    private string $secretKey;
    private int    $merchantUserId;

    // Click action types
    const ACTION_PREPARE  = 0;
    const ACTION_COMPLETE = 1;

    // Click error codes
    const SUCCESS               = 0;
    const ERROR_SIGN            = -1;
    const ERROR_INVALID_AMOUNT  = -2;
    const ERROR_ORDER_NOT_FOUND = -5;
    const ERROR_ALREADY_PAID    = -4;
    const ERROR_TRANSACTION      = -9;

    public function __construct()
    {
        $this->serviceId      = (int)config('services.click.service_id');
        $this->merchantId     = (int)config('services.click.merchant_id');
        $this->secretKey      = config('services.click.secret_key');
        $this->merchantUserId = (int)config('services.click.merchant_user_id');
    }

    /**
     * Generate Click payment URL
     */
    public function generatePaymentUrl(Booking $booking): string
    {
        $params = http_build_query([
            'service_id'          => $this->serviceId,
            'merchant_id'         => $this->merchantId,
            'amount'              => $booking->final_amount,
            'transaction_param'   => $booking->id,
            'return_url'          => url('/api/v1/payment/click/return'),
        ]);

        return "https://my.click.uz/services/pay?{$params}";
    }

    /**
     * Handle Click Prepare request
     */
    public function prepare(array $data): array
    {
        // Verify sign
        if (!$this->verifySign($data, self::ACTION_PREPARE)) {
            return $this->response(
                $data['click_trans_id'] ?? 0,
                $data['merchant_trans_id'] ?? 0,
                self::ERROR_SIGN,
                'Invalid sign'
            );
        }

        $bookingId = $data['merchant_trans_id'];
        $amount    = (float)$data['amount'];

        $booking = Booking::find($bookingId);

        if (!$booking) {
            return $this->response(
                $data['click_trans_id'],
                $bookingId,
                self::ERROR_ORDER_NOT_FOUND,
                'Order not found'
            );
        }

        if ($booking->payment_status === 'paid') {
            return $this->response(
                $data['click_trans_id'],
                $bookingId,
                self::ERROR_ALREADY_PAID,
                'Already paid'
            );
        }

        if ((float)$booking->final_amount !== $amount) {
            return $this->response(
                $data['click_trans_id'],
                $bookingId,
                self::ERROR_INVALID_AMOUNT,
                'Invalid amount'
            );
        }

        // Create or update payment
        Payment::updateOrCreate(
            [
                'provider_transaction_id' => (string)$data['click_trans_id'],
                'provider'                => 'click',
            ],
            [
                'user_id'    => $booking->user_id,
                'booking_id' => $booking->id,
                'amount'     => $amount,
                'currency'   => 'UZS',
                'status'     => 'pending',
                'provider_response' => $data,
            ]
        );

        return $this->response(
            $data['click_trans_id'],
            $bookingId,
            self::SUCCESS,
            'OK',
            now()->timestamp
        );
    }

    /**
     * Handle Click Complete request
     */
    public function complete(array $data): array
    {
        // Verify sign
        if (!$this->verifySign($data, self::ACTION_COMPLETE)) {
            return $this->response(
                $data['click_trans_id'] ?? 0,
                $data['merchant_trans_id'] ?? 0,
                self::ERROR_SIGN,
                'Invalid sign'
            );
        }

        $bookingId = $data['merchant_trans_id'];
        $clickTransId = (string)$data['click_trans_id'];

        $payment = Payment::where('provider_transaction_id', $clickTransId)
            ->where('provider', 'click')
            ->first();

        if (!$payment) {
            return $this->response(
                $data['click_trans_id'],
                $bookingId,
                self::ERROR_TRANSACTION,
                'Transaction not found'
            );
        }

        $error = (int)($data['error'] ?? 0);

        if ($error < 0) {
            // Payment failed/cancelled
            $payment->update(['status' => 'cancelled']);
            return $this->response(
                $data['click_trans_id'],
                $bookingId,
                self::ERROR_TRANSACTION,
                'Transaction cancelled'
            );
        }

        $now = now();
        $payment->update([
            'status'  => 'completed',
            'paid_at' => $now,
            'provider_response' => $data,
        ]);

        // Update booking
        $booking = Booking::find($bookingId);
        if ($booking) {
            $booking->update([
                'payment_status' => 'paid',
                'payment_method' => 'click',
                'status'         => 'confirmed',
                'confirmed_at'   => $now,
                'transaction_id' => $clickTransId,
            ]);
        }

        return $this->response(
            $data['click_trans_id'],
            $bookingId,
            self::SUCCESS,
            'OK'
        );
    }

    private function verifySign(array $data, int $action): bool
    {
        $signStr = $data['click_trans_id']
            . $this->serviceId
            . $this->secretKey
            . $data['merchant_trans_id']
            . (isset($data['merchant_prepare_id']) ? $data['merchant_prepare_id'] : '')
            . $data['amount']
            . $data['action']
            . $data['sign_time'];

        $expectedSign = md5($signStr);
        return $expectedSign === $data['sign_string'];
    }

    private function response(
        $clickTransId,
        $merchantTransId,
        int $error,
        string $errorNote,
        ?int $prepareId = null
    ): array {
        $result = [
            'click_trans_id'    => $clickTransId,
            'merchant_trans_id' => $merchantTransId,
            'error'             => $error,
            'error_note'        => $errorNote,
        ];

        if ($prepareId !== null) {
            $result['merchant_prepare_id'] = $prepareId;
        }

        return $result;
    }
}
