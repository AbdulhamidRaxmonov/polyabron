<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Venue;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookingController extends Controller
{
    /**
     * GET /api/v1/bookings
     * User's bookings list
     */
    public function index(Request $request)
    {
        $user = JWTAuth::user();

        $query = Booking::with(['venue.category'])
            ->where('user_id', $user->id)
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $bookings->items(),
            'meta'   => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/bookings
     * Create a new booking
     */
    public function store(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'venue_id'    => 'required|exists:venues,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'notes'       => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $venue = Venue::where('status', 'active')->findOrFail($request->venue_id);

        // Check time availability
        $conflict = Booking::where('venue_id', $venue->id)
            ->where('booking_date', $request->booking_date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                  ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_time', '<=', $request->start_time)
                         ->where('end_time', '>=', $request->end_time);
                  });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'status'  => false,
                'message' => 'Bu vaqt allaqachon band. Boshqa vaqt tanlang.',
            ], 409);
        }

        // Calculate duration and amount
        $start    = \Carbon\Carbon::createFromFormat('H:i', $request->start_time);
        $end      = \Carbon\Carbon::createFromFormat('H:i', $request->end_time);
        $duration = $end->diffInHours($start);

        if ($duration < $venue->min_booking_hours) {
            return response()->json([
                'status'  => false,
                'message' => "Minimal bron vaqti {$venue->min_booking_hours} soat",
            ], 422);
        }

        // Weekend price check
        $isWeekend = \Carbon\Carbon::parse($request->booking_date)->isWeekend();
        $pricePerHour = ($isWeekend && $venue->price_weekend)
            ? $venue->price_weekend
            : $venue->price_per_hour;

        $totalAmount = $pricePerHour * $duration;

        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'user_id'        => $user->id,
                'venue_id'       => $venue->id,
                'booking_date'   => $request->booking_date,
                'start_time'     => $request->start_time . ':00',
                'end_time'       => $request->end_time . ':00',
                'duration_hours' => $duration,
                'price_per_hour' => $pricePerHour,
                'total_amount'   => $totalAmount,
                'discount_amount' => 0,
                'final_amount'   => $totalAmount,
                'status'         => 'pending',
                'payment_status' => 'unpaid',
                'notes'          => $request->notes,
            ]);

            // Notify venue owner
            AppNotification::create([
                'user_id' => $venue->owner_id,
                'title'   => 'Yangi bron!',
                'body'    => "{$user->name} sizning maydoningizni bron qildi",
                'type'    => 'booking_confirmed',
                'data'    => ['booking_id' => $booking->id],
            ]);

            // Increment venue bookings count
            $venue->increment('bookings_count');

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Bron muvaffaqiyatli yaratildi',
                'data'    => $this->transformBooking($booking->load('venue')),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Xatolik yuz berdi. Qayta urinib ko\'ring.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/bookings/{id}
     */
    public function show($id)
    {
        $user    = JWTAuth::user();
        $booking = Booking::with(['venue.category', 'payment'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $this->transformBooking($booking),
        ]);
    }

    /**
     * POST /api/v1/bookings/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $user    = JWTAuth::user();
        $booking = Booking::where('user_id', $user->id)->findOrFail($id);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Bu bronni bekor qilib bo\'lmaydi',
            ], 400);
        }

        // Check cancellation time (at least 2 hours before)
        $bookingStart = \Carbon\Carbon::parse(
            $booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time
        );

        if ($bookingStart->diffInHours(now()) < 2 && $bookingStart->isFuture()) {
            // Allow but warn
        }

        $booking->update([
            'status'        => 'cancelled',
            'cancel_reason' => $request->reason ?? 'Foydalanuvchi tomonidan bekor qilindi',
            'cancelled_at'  => now(),
        ]);

        // Refund logic (if paid)
        if ($booking->payment_status === 'paid') {
            // TODO: Process refund
        }

        return response()->json([
            'status'  => true,
            'message' => 'Bron bekor qilindi',
        ]);
    }

    /**
     * GET /api/v1/owner/bookings
     * Owner sees bookings for their venues
     */
    public function ownerBookings(Request $request)
    {
        $user = JWTAuth::user();

        $venueIds = Venue::where('owner_id', $user->id)->pluck('id');

        $query = Booking::with(['user', 'venue'])
            ->whereIn('venue_id', $venueIds)
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->where('booking_date', $request->date);
        }

        $bookings = $query->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $bookings->items(),
            'meta'   => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'total'        => $bookings->total(),
            ],
        ]);
    }

    private function transformBooking(Booking $booking): array
    {
        return [
            'id'              => $booking->id,
            'booking_number'  => $booking->booking_number,
            'booking_date'    => $booking->booking_date->format('Y-m-d'),
            'start_time'      => substr($booking->start_time, 0, 5),
            'end_time'        => substr($booking->end_time, 0, 5),
            'duration_hours'  => $booking->duration_hours,
            'price_per_hour'  => (float)$booking->price_per_hour,
            'total_amount'    => (float)$booking->total_amount,
            'final_amount'    => (float)$booking->final_amount,
            'status'          => $booking->status,
            'payment_status'  => $booking->payment_status,
            'payment_method'  => $booking->payment_method,
            'notes'           => $booking->notes,
            'cancel_reason'   => $booking->cancel_reason,
            'created_at'      => $booking->created_at->toISOString(),
            'venue'           => $booking->venue ? [
                'id'         => $booking->venue->id,
                'name'       => $booking->venue->name,
                'address'    => $booking->venue->address,
                'cover_image' => $booking->venue->cover_image
                    ? asset('storage/' . $booking->venue->cover_image)
                    : null,
                'latitude'   => (float)$booking->venue->latitude,
                'longitude'  => (float)$booking->venue->longitude,
            ] : null,
        ];
    }
}
