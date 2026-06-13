<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::latest();

        if ($request->filled('role'))   $query->where('role', $request->role);
        if ($request->filled('status')) $query->where('is_active', $request->status === 'active');
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('phone', 'LIKE', "%{$request->search}%")
                  ->orWhere('email', 'LIKE', "%{$request->search}%");
            });
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total'   => User::count(),
            'users'   => User::where('role', 'user')->count(),
            'owners'  => User::where('role', 'owner')->count(),
            'admins'  => User::where('role', 'admin')->count(),
            'blocked' => User::where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function show($id)
    {
        $user = User::with(['bookings.venue', 'venues', 'reviews'])->findOrFail($id);

        $userStats = [
            'total_bookings'    => $user->bookings()->count(),
            'completed_bookings' => $user->bookings()->where('status', 'completed')->count(),
            'total_spent'       => $user->bookings()->where('payment_status', 'paid')->sum('final_amount'),
            'venues_count'      => $user->venues()->count(),
        ];

        return view('admin.users.show', compact('user', 'userStats'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|unique:users,phone,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'role'  => 'required|in:user,owner,admin',
        ]);

        $data = $request->only(['name', 'phone', 'email', 'role', 'is_active']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.show', $id)
            ->with('success', 'Foydalanuvchi ma\'lumotlari yangilandi');
    }

    public function toggleBlock($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);

        $msg = $user->is_active ? 'Foydalanuvchi faollashtirildi' : 'Foydalanuvchi bloklandi';
        return back()->with('success', $msg);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|unique:users,phone',
            'email'    => 'nullable|email|unique:users,email',
            'role'     => 'required|in:user,owner,admin',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'        => $request->name,
            'phone'       => $request->phone,
            'email'       => $request->email,
            'role'        => $request->role,
            'password'    => Hash::make($request->password),
            'is_verified' => true,
            'is_active'   => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Foydalanuvchi yaratildi');
    }
}
