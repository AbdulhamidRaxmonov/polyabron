<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\PaymeService;
use App\Services\ClickService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller
{
    protected PaymeService $paymeService;
    protected ClickService $clickService;

    public function __construct(PaymeService $paymeService, ClickService $clickService)
    {
        $this->paymeService  = $paymeService;
        $this->clickService  = $clickService;
    }

    // ─── Payme ─────────────────────────────────────────────────

    /**
     * GET /api/v1/payment/payme/url/{bookingId}
     * Get Payme checkout URL
     */
    public function paymeUrl($bookingId)
    {
        $user    = JWTAuth::user();
        $booking = Booking::where('user_id', $user->id)->findOrFail($bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json(['status' => false, 'message' => 'Allaqachon to\'langan'], 400);
        }

        $url = $this->paymeService->generateCheckoutUrl($booking);

        return response()->json([
            'status'      => true,
            'payment_url' => $url,
            'amount'      => $booking->final_amount,
        ]);
    }

    /**
     * POST /api/v1/payment/payme/merchant
     * Payme Merchant API endpoint
     */
    public function paymeMerchant(Request $request)
    {
        $authorization = $request->header('Authorization', '');
        $result = $this->paymeService->handle($request->all(), $authorization);

        return response()->json($result);
    }

    // ─── Click ─────────────────────────────────────────────────

    /**
     * GET /api/v1/payment/click/url/{bookingId}
     * Get Click payment URL
     */
    public function clickUrl($bookingId)
    {
        $user    = JWTAuth::user();
        $booking = Booking::where('user_id', $user->id)->findOrFail($bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json(['status' => false, 'message' => 'Allaqachon to\'langan'], 400);
        }

        $url = $this->clickService->generatePaymentUrl($booking);

        return response()->json([
            'status'      => true,
            'payment_url' => $url,
            'amount'      => $booking->final_amount,
        ]);
    }

    /**
     * POST /api/v1/payment/click/prepare
     * Click prepare endpoint
     */
    public function clickPrepare(Request $request)
    {
        $result = $this->clickService->prepare($request->all());
        return response()->json($result);
    }

    /**
     * POST /api/v1/payment/click/complete
     * Click complete endpoint
     */
    public function clickComplete(Request $request)
    {
        $result = $this->clickService->complete($request->all());
        return response()->json($result);
    }

    /**
     * GET /api/v1/payment/history
     * User payment history
     */
    public function history(Request $request)
    {
        $user     = JWTAuth::user();
        $payments = $user->payments()
            ->with('booking.venue')
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $payments->items(),
            'meta'   => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }
}
