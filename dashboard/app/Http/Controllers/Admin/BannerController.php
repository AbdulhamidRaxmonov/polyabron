<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Venue;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::with('venue')->orderBy('sort_order')->paginate(15);
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        $venues = Venue::where('status', 'active')->get();
        return view('admin.banners.create', compact('venues'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'    => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'type'     => 'required|in:venue,promo,external',
        ]);

        $data = $request->only(['title_uz', 'title_ru', 'link', 'type', 'venue_id', 'sort_order', 'starts_at', 'ends_at']);
        $data['is_active'] = $request->has('is_active');
        $data['image']     = $request->file('image')->store('banners', 'public');

        Banner::create($data);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner yaratildi');
    }

    public function edit($id)
    {
        $banner = Banner::findOrFail($id);
        $venues = Venue::where('status', 'active')->get();
        return view('admin.banners.edit', compact('banner', 'venues'));
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $data = $request->only(['title_uz', 'title_ru', 'link', 'type', 'venue_id', 'sort_order', 'starts_at', 'ends_at']);
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner yangilandi');
    }

    public function destroy($id)
    {
        Banner::findOrFail($id)->delete();
        return back()->with('success', 'Banner o\'chirildi');
    }
}
