<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/profile
     */
    public function show()
    {
        $user = JWTAuth::user();

        return response()->json([
            'status' => true,
            'data'   => [
                'id'          => $user->id,
                'name'        => $user->name,
                'phone'       => $user->phone,
                'email'       => $user->email,
                'avatar'      => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'role'        => $user->role,
                'balance'     => $user->balance,
                'lang'        => $user->lang,
                'is_verified' => $user->is_verified,
                'created_at'  => $user->created_at->toISOString(),
                'stats'       => [
                    'total_bookings'    => $user->bookings()->count(),
                    'completed_bookings' => $user->bookings()->where('status', 'completed')->count(),
                    'total_spent'       => $user->bookings()
                        ->where('payment_status', 'paid')
                        ->sum('final_amount'),
                ],
            ],
        ]);
    }

    /**
     * PUT /api/v1/profile
     */
    public function update(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'lang'  => 'sometimes|in:uz,ru,en',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'lang']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Profil yangilandi',
            'data'    => $user->fresh(),
        ]);
    }

    /**
     * PUT /api/v1/profile/password
     */
    public function changePassword(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Joriy parol noto\'g\'ri',
            ], 400);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status'  => true,
            'message' => 'Parol muvaffaqiyatli o\'zgartirildi',
        ]);
    }

    /**
     * POST /api/v1/profile/favorites/{venueId}
     * Toggle favorite
     */
    public function toggleFavorite($venueId)
    {
        $user  = JWTAuth::user();
        $venue = Venue::findOrFail($venueId);

        $existing = $user->favorites()->where('venue_id', $venue->id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'status'      => true,
                'is_favorite' => false,
                'message'     => 'Sevimlilardab o\'chirildi',
            ]);
        }

        $user->favorites()->create(['venue_id' => $venue->id]);

        return response()->json([
            'status'      => true,
            'is_favorite' => true,
            'message'     => 'Sevimlilarga qo\'shildi',
        ]);
    }

    /**
     * GET /api/v1/profile/favorites
     */
    public function favorites()
    {
        $user    = JWTAuth::user();
        $venues  = $user->favoriteVenues()
            ->with('category')
            ->where('status', 'active')
            ->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => $venues->items(),
        ]);
    }

    /**
     * GET /api/v1/profile/notifications
     */
    public function notifications(Request $request)
    {
        $user          = JWTAuth::user();
        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'status'       => true,
            'data'         => $notifications->items(),
            'unread_count' => $user->notifications()->where('is_read', false)->count(),
        ]);
    }

    /**
     * POST /api/v1/profile/notifications/read-all
     */
    public function markAllRead()
    {
        $user = JWTAuth::user();
        $user->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['status' => true, 'message' => 'Hammasi o\'qildi']);
    }

    /**
     * PUT /api/v1/profile/fcm-token
     */
    public function updateFcmToken(Request $request)
    {
        $user = JWTAuth::user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['status' => true]);
    }
}
