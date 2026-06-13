<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'venue'])->latest();

        if ($request->filled('visible')) $query->where('is_visible', $request->visible === '1');
        if ($request->filled('rating'))  $query->where('rating', $request->rating);
        if ($request->filled('search'))  {
            $query->whereHas('venue', fn($q) => $q->where('name', 'LIKE', "%{$request->search}%"));
        }

        $reviews = $query->paginate(20)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function toggleVisibility($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_visible' => !$review->is_visible]);
        $review->venue->updateRating();

        return back()->with('success', 'Izoh holati o\'zgartirildi');
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $venueId = $review->venue_id;
        $review->delete();
        \App\Models\Venue::findOrFail($venueId)->updateRating();

        return back()->with('success', 'Izoh o\'chirildi');
    }
}
