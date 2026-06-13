<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class VenueController extends Controller
{
    /**
     * GET /api/v1/venues
     * List venues with filters
     */
    public function index(Request $request)
    {
        $query = Venue::with(['category', 'owner'])
            ->active();

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // City filter
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Price filter
        if ($request->has('min_price')) {
            $query->where('price_per_hour', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price_per_hour', '<=', $request->max_price);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('address', 'LIKE', "%{$request->search}%");
            });
        }

        // Nearby (by lat/lng)
        if ($request->has('lat') && $request->has('lng')) {
            $radius = $request->radius ?? 10;
            $query->nearby($request->lat, $request->lng, $radius);
        }

        // Sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDir = $request->sort_dir ?? 'desc';
        if ($sortBy === 'rating') {
            $query->orderBy('rating', 'desc');
        } elseif ($sortBy === 'price_asc') {
            $query->orderBy('price_per_hour', 'asc');
        } elseif ($sortBy === 'price_desc') {
            $query->orderBy('price_per_hour', 'desc');
        } else {
            $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
        }

        $venues = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => true,
            'data'   => $this->transformVenues($venues->items()),
            'meta'   => [
                'current_page' => $venues->currentPage(),
                'last_page'    => $venues->lastPage(),
                'per_page'     => $venues->perPage(),
                'total'        => $venues->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/venues/featured
     */
    public function featured()
    {
        $venues = Venue::with(['category'])
            ->active()
            ->featured()
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $this->transformVenues($venues->toArray()),
        ]);
    }

    /**
     * GET /api/v1/venues/nearby
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'numeric|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $venues = Venue::with(['category'])
            ->active()
            ->nearby($request->lat, $request->lng, $request->radius ?? 10)
            ->limit(20)
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $this->transformVenues($venues->toArray()),
        ]);
    }

    /**
     * GET /api/v1/venues/{id}
     */
    public function show($id)
    {
        $venue = Venue::with(['category', 'owner', 'reviews.user'])
            ->where('status', 'active')
            ->findOrFail($id);

        $user = null;
        try {
            $user = JWTAuth::user();
        } catch (\Exception $e) {}

        $isFavorite = false;
        if ($user) {
            $isFavorite = $user->favoriteVenues()->where('venue_id', $venue->id)->exists();
        }

        return response()->json([
            'status' => true,
            'data'   => array_merge($this->transformVenue($venue), [
                'is_favorite' => $isFavorite,
                'reviews'     => $venue->reviews->where('is_visible', true)->take(5)->map(function ($r) {
                    return [
                        'id'      => $r->id,
                        'rating'  => $r->rating,
                        'comment' => $r->comment,
                        'images'  => $r->images,
                        'user'    => [
                            'name'   => $r->user->name,
                            'avatar' => $r->user->avatar ? asset('storage/' . $r->user->avatar) : null,
                        ],
                        'created_at' => $r->created_at->diffForHumans(),
                    ];
                })->values(),
            ]),
        ]);
    }

    /**
     * GET /api/v1/venues/{id}/availability
     */
    public function availability(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $venue = Venue::findOrFail($id);

        // Get booked slots for the date
        $bookedSlots = \App\Models\Booking::where('venue_id', $id)
            ->where('booking_date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->get(['start_time', 'end_time']);

        // Generate all slots
        $slots = [];
        $openHour  = (int)substr($venue->open_time, 0, 2);
        $closeHour = (int)substr($venue->close_time, 0, 2);

        for ($hour = $openHour; $hour < $closeHour; $hour++) {
            $start  = sprintf('%02d:00', $hour);
            $end    = sprintf('%02d:00', $hour + 1);
            $booked = false;

            foreach ($bookedSlots as $slot) {
                $slotStart = substr($slot->start_time, 0, 5);
                $slotEnd   = substr($slot->end_time, 0, 5);
                if ($start >= $slotStart && $start < $slotEnd) {
                    $booked = true;
                    break;
                }
            }

            $slots[] = [
                'start_time' => $start,
                'end_time'   => $end,
                'is_booked'  => $booked,
                'price'      => $venue->price_per_hour,
            ];
        }

        return response()->json([
            'status' => true,
            'date'   => $request->date,
            'slots'  => $slots,
        ]);
    }

    /**
     * POST /api/v1/owner/venues — Owner create venue
     */
    public function store(Request $request)
    {
        $user = JWTAuth::user();

        $validator = Validator::make($request->all(), [
            'category_id'     => 'required|exists:categories,id',
            'name'            => 'required|string|max:200',
            'description'     => 'nullable|string',
            'address'         => 'required|string',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'city'            => 'required|string',
            'phone'           => 'nullable|string',
            'price_per_hour'  => 'required|numeric|min:0',
            'open_time'       => 'required|date_format:H:i',
            'close_time'      => 'required|date_format:H:i',
            'amenities'       => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'category_id', 'name', 'description', 'address',
            'latitude', 'longitude', 'city', 'district', 'phone',
            'price_per_hour', 'price_weekend',
            'min_booking_hours', 'max_booking_hours',
            'open_time', 'close_time', 'amenities',
        ]);

        $data['owner_id'] = $user->id;
        $data['status']   = 'pending'; // Requires admin approval

        // Handle images upload
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('venues', 'public');
            $data['cover_image'] = $path;
        }

        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $img) {
                $paths[] = $img->store('venues', 'public');
            }
            $data['images'] = $paths;
        }

        $venue = Venue::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Maydon muvaffaqiyatli qo\'shildi. Admin tasdiqlashini kuting.',
            'data'    => $this->transformVenue($venue),
        ], 201);
    }

    /**
     * PUT /api/v1/owner/venues/{id}
     */
    public function update(Request $request, $id)
    {
        $user  = JWTAuth::user();
        $venue = Venue::where('owner_id', $user->id)->findOrFail($id);

        $data = $request->only([
            'name', 'description', 'address', 'city', 'district',
            'phone', 'price_per_hour', 'price_weekend',
            'min_booking_hours', 'max_booking_hours',
            'open_time', 'close_time', 'amenities',
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('venues', 'public');
            $data['cover_image'] = $path;
        }

        $venue->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Maydon ma\'lumotlari yangilandi',
            'data'    => $this->transformVenue($venue->fresh()),
        ]);
    }

    // Helpers
    private function transformVenues(array $venues): array
    {
        return array_map(fn($v) => $this->transformVenue((object)$v), $venues);
    }

    private function transformVenue($venue): array
    {
        return [
            'id'              => $venue->id,
            'name'            => $venue->name,
            'description'     => $venue->description,
            'address'         => $venue->address,
            'latitude'        => (float)$venue->latitude,
            'longitude'       => (float)$venue->longitude,
            'city'            => $venue->city,
            'phone'           => $venue->phone,
            'price_per_hour'  => (float)$venue->price_per_hour,
            'price_weekend'   => $venue->price_weekend ? (float)$venue->price_weekend : null,
            'open_time'       => $venue->open_time,
            'close_time'      => $venue->close_time,
            'rating'          => (float)$venue->rating,
            'reviews_count'   => $venue->reviews_count,
            'bookings_count'  => $venue->bookings_count,
            'is_featured'     => (bool)$venue->is_featured,
            'amenities'       => is_string($venue->amenities) ? json_decode($venue->amenities, true) : $venue->amenities,
            'cover_image'     => $venue->cover_image ? asset('storage/' . $venue->cover_image) : null,
            'images'          => $venue->images
                ? array_map(fn($img) => asset('storage/' . $img),
                    is_string($venue->images) ? json_decode($venue->images, true) ?? [] : ($venue->images ?? []))
                : [],
            'category'        => isset($venue->category) ? [
                'id'   => $venue->category->id ?? null,
                'name' => $venue->category->name_uz ?? null,
                'icon' => $venue->category->icon ?? null,
            ] : null,
            'distance'        => isset($venue->distance) ? round($venue->distance, 2) : null,
        ];
    }
}
