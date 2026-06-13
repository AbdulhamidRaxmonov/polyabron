<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'venue'])->latest();

        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('payment'))  $query->where('payment_status', $request->payment);
        if ($request->filled('date'))     $query->where('booking_date', $request->date);
        if ($request->filled('search'))   {
            $query->where(function($q) use ($request) {
                $q->where('booking_number', 'LIKE', "%{$request->search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$request->search}%"));
            });
        }

        $bookings = $query->paginate(20)->withQueryString();

        $stats = [
            'total'     => Booking::count(),
            'pending'   => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
        ];

        return view('admin.bookings.index', compact('bookings', 'stats'));
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'venue.category', 'payment', 'review'])->findOrFail($id);
        return view('admin.bookings.show', compact('booking'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled,completed,no_show']);

        $booking = Booking::findOrFail($id);
        $data    = ['status' => $request->status];

        if ($request->status === 'confirmed') $data['confirmed_at'] = now();
        if ($request->status === 'cancelled') {
            $data['cancelled_at']  = now();
            $data['cancel_reason'] = $request->reason ?? 'Admin tomonidan bekor qilindi';
        }
        if ($request->status === 'completed') $data['completed_at'] = now();

        $booking->update($data);

        return back()->with('success', 'Bron holati yangilandi');
    }

    public function export(Request $request)
    {
        $bookings = Booking::with(['user', 'venue'])
            ->when($request->from, fn($q) => $q->where('booking_date', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->where('booking_date', '<=', $request->to))
            ->get();

        $csv = "ID,Bron raqami,Foydalanuvchi,Maydon,Sana,Boshlanish,Tugash,Summa,Holat\n";
        foreach ($bookings as $b) {
            $csv .= "\"{$b->id}\",\"{$b->booking_number}\",\"{$b->user->name}\",\"{$b->venue->name}\","
                . "\"{$b->booking_date}\",\"{$b->start_time}\",\"{$b->end_time}\","
                . "\"{$b->final_amount}\",\"{$b->status}\"\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="bookings.csv"');
    }
}
