<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Venue;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * GET /api/v1/home
     * Home screen data: banners, categories, featured venues, nearby
     */
    public function index(Request $request)
    {
        // Banners
        $banners = Banner::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderBy('sort_order')
            ->get()
            ->map(fn($b) => [
                'id'       => $b->id,
                'title'    => $b->title_uz,
                'image'    => asset('storage/' . $b->image),
                'link'     => $b->link,
                'type'     => $b->type,
                'venue_id' => $b->venue_id,
            ]);

        // Categories
        $categories = Category::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => [
                'id'    => $c->id,
                'name'  => $c->name_uz,
                'icon'  => $c->icon,
                'image' => $c->image ? asset('storage/' . $c->image) : null,
            ]);

        // Featured venues
        $featured = Venue::with('category')
            ->active()
            ->featured()
            ->orderByDesc('rating')
            ->limit(10)
            ->get()
            ->map(fn($v) => $this->venueCard($v));

        // Nearby (if coords provided)
        $nearby = collect();
        if ($request->has('lat') && $request->has('lng')) {
            $nearby = Venue::with('category')
                ->active()
                ->nearby((float)$request->lat, (float)$request->lng, 5)
                ->limit(10)
                ->get()
                ->map(fn($v) => $this->venueCard($v));
        }

        // New venues
        $newVenues = Venue::with('category')
            ->active()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($v) => $this->venueCard($v));

        return response()->json([
            'status' => true,
            'data'   => [
                'banners'    => $banners,
                'categories' => $categories,
                'featured'   => $featured,
                'nearby'     => $nearby,
                'new_venues' => $newVenues,
            ],
        ]);
    }

    /**
     * GET /api/v1/categories
     */
    public function categories()
    {
        $categories = Category::where('is_active', true)
            ->withCount(['venues' => fn($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name_uz'      => $c->name_uz,
                'name_ru'      => $c->name_ru,
                'icon'         => $c->icon,
                'image'        => $c->image ? asset('storage/' . $c->image) : null,
                'venues_count' => $c->venues_count,
            ]);

        return response()->json([
            'status' => true,
            'data'   => $categories,
        ]);
    }

    private function venueCard(Venue $venue): array
    {
        return [
            'id'             => $venue->id,
            'name'           => $venue->name,
            'address'        => $venue->address,
            'price_per_hour' => (float)$venue->price_per_hour,
            'rating'         => (float)$venue->rating,
            'reviews_count'  => $venue->reviews_count,
            'is_featured'    => (bool)$venue->is_featured,
            'cover_image'    => $venue->cover_image ? asset('storage/' . $venue->cover_image) : null,
            'latitude'       => (float)$venue->latitude,
            'longitude'      => (float)$venue->longitude,
            'distance'       => isset($venue->distance) ? round($venue->distance, 2) : null,
            'category'       => $venue->category ? [
                'id'   => $venue->category->id,
                'name' => $venue->category->name_uz,
                'icon' => $venue->category->icon,
            ] : null,
        ];
    }
}
