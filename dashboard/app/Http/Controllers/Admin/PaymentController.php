<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'booking.venue'])->latest();

        if ($request->filled('provider')) $query->where('provider', $request->provider);
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('from'))     $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))       $query->whereDate('created_at', '<=', $request->to);

        $payments = $query->paginate(20)->withQueryString();

        $stats = [
            'total'          => Payment::where('status', 'completed')->sum('amount'),
            'payme'          => Payment::where('provider', 'payme')->where('status', 'completed')->sum('amount'),
            'click'          => Payment::where('provider', 'click')->where('status', 'completed')->sum('amount'),
            'cash'           => Payment::where('provider', 'cash')->where('status', 'completed')->sum('amount'),
            'pending_count'  => Payment::where('status', 'pending')->count(),
            'today'          => Payment::where('status', 'completed')->whereDate('created_at', today())->sum('amount'),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function show($id)
    {
        $payment = Payment::with(['user', 'booking.venue'])->findOrFail($id);
        return view('admin.payments.show', compact('payment'));
    }
}
