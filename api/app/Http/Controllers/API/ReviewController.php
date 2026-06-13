<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReviewController extends Controller
{
    /**
     * GET /api/v1/venues/{venueId}/reviews
     */
    public function index($venueId)
    {
        $reviews = Review::with('user')
            ->where('venue_id', $venueId)
            ->where('is_visible', true)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data'   => $reviews->items(),
            'meta'   => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/reviews
     */
    public function store(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $booking = Booking::where('user_id', $user->id)
            ->where('status', 'completed')
            ->findOrFail($request->booking_id);

        // Check if already reviewed
        $exists = Review::where('booking_id', $booking->id)->exists();
        if ($exists) {
            return response()->json([
                'status'  => false,
                'message' => 'Bu bron uchun allaqachon izoh qoldirgan siz',
            ], 409);
        }

        $data = [
            'user_id'    => $user->id,
            'venue_id'   => $booking->venue_id,
            'booking_id' => $booking->id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
        ];

        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $img) {
                $images[] = $img->store('reviews', 'public');
            }
            $data['images'] = $images;
        }

        $review = Review::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Izoh muvaffaqiyatli qoldirildi',
            'data'    => $review,
        ], 201);
    }

    /**
     * POST /api/v1/reviews/{id}/reply
     * Owner reply to review
     */
    public function reply(Request $request, $id)
    {
        $user   = JWTAuth::user();
        $review = Review::findOrFail($id);

        // Check ownership
        $isOwner = \App\Models\Venue::where('id', $review->venue_id)
            ->where('owner_id', $user->id)
            ->exists();

        if (!$isOwner) {
            return response()->json(['status' => false, 'message' => 'Ruxsat yo\'q'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $review->update([
            'owner_reply'      => $request->reply,
            'owner_replied_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Javob qo\'shildi',
        ]);
    }
}
