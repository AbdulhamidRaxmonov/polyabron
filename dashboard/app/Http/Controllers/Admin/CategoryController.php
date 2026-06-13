<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['venues' => fn($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_uz'    => 'required|string|max:100',
            'name_ru'    => 'required|string|max:100',
            'sort_order' => 'integer|min:0',
        ]);

        $data = $request->only(['name_uz', 'name_ru', 'name_en', 'sort_order', 'is_active']);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategoriya yaratildi');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name_uz' => 'required|string|max:100',
            'name_ru' => 'required|string|max:100',
        ]);

        $data = $request->only(['name_uz', 'name_ru', 'name_en', 'sort_order']);
        $data['is_active'] = $request->has('is_active');

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategoriya yangilandi');
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();
        return back()->with('success', 'Kategoriya o\'chirildi');
    }
}
