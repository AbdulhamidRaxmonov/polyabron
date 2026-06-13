<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(Request $request)
    {
        $query = Venue::with(['owner', 'category'])->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('category')) $query->where('category_id', $request->category);
        if ($request->filled('search'))   $query->where('name', 'LIKE', "%{$request->search}%");

        $venues     = $query->paginate(15)->withQueryString();
        $categories = Category::where('is_active', true)->get();

        return view('admin.venues.index', compact('venues', 'categories'));
    }

    public function show($id)
    {
        $venue = Venue::with(['owner', 'category', 'reviews.user', 'bookings'])->findOrFail($id);
        return view('admin.venues.show', compact('venue'));
    }

    public function edit($id)
    {
        $venue      = Venue::findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        $owners     = User::where('role', 'owner')->get();
        return view('admin.venues.edit', compact('venue', 'categories', 'owners'));
    }

    public function update(Request $request, $id)
    {
        $venue = Venue::findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:200',
            'category_id'    => 'required|exists:categories,id',
            'address'        => 'required|string',
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'price_per_hour' => 'required|numeric|min:0',
        ]);

        $data = $request->only([
            'name', 'description', 'category_id', 'address',
            'latitude', 'longitude', 'city', 'district', 'phone',
            'price_per_hour', 'price_weekend', 'open_time', 'close_time',
            'min_booking_hours', 'max_booking_hours', 'is_featured',
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('venues', 'public');
            $data['cover_image'] = $path;
        }

        $venue->update($data);

        return redirect()->route('admin.venues.show', $id)
            ->with('success', 'Maydon ma\'lumotlari yangilandi');
    }

    public function approve($id)
    {
        $venue = Venue::findOrFail($id);
        $venue->update(['status' => 'active']);

        // Notify owner
        \App\Models\AppNotification::create([
            'user_id' => $venue->owner_id,
            'title'   => 'Maydoningiz tasdiqlandi!',
            'body'    => "{$venue->name} aktiv holatga o'tdi.",
            'type'    => 'system',
            'data'    => ['venue_id' => $venue->id],
        ]);

        return back()->with('success', 'Maydon tasdiqlandi va faollashtirildi');
    }

    public function reject(Request $request, $id)
    {
        $venue = Venue::findOrFail($id);
        $venue->update(['status' => 'rejected']);

        \App\Models\AppNotification::create([
            'user_id' => $venue->owner_id,
            'title'   => 'Maydoningiz rad etildi',
            'body'    => $request->reason ?? 'Maydoningiz admin tomonidan rad etildi.',
            'type'    => 'system',
            'data'    => ['venue_id' => $venue->id],
        ]);

        return back()->with('error', 'Maydon rad etildi');
    }

    public function toggleFeatured($id)
    {
        $venue = Venue::findOrFail($id);
        $venue->update(['is_featured' => !$venue->is_featured]);

        return back()->with('success', $venue->is_featured ? 'Tavsiya etilganlar ro\'yxatiga qo\'shildi' : 'Ro\'yxatdan olib tashlandi');
    }

    public function destroy($id)
    {
        $venue = Venue::findOrFail($id);
        $venue->delete();
        return redirect()->route('admin.venues.index')
            ->with('success', 'Maydon o\'chirildi');
    }
}
