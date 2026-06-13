<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Venue;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Overview stats
        $stats = [
            'total_users'    => User::where('role', 'user')->count(),
            'total_owners'   => User::where('role', 'owner')->count(),
            'total_venues'   => Venue::where('status', 'active')->count(),
            'pending_venues' => Venue::where('status', 'pending')->count(),
            'total_bookings' => Booking::count(),
            'today_bookings' => Booking::whereDate('created_at', $today)->count(),
            'total_revenue'  => Payment::where('status', 'completed')->sum('amount'),
            'month_revenue'  => Payment::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)->sum('amount'),
            'last_month_revenue' => Payment::where('status', 'completed')
                ->whereBetween('created_at', [$lastMonth, $thisMonth])->sum('amount'),
            'pending_reviews' => Review::where('is_visible', false)->count(),
        ];

        // Revenue growth %
        $stats['revenue_growth'] = $stats['last_month_revenue'] > 0
            ? round((($stats['month_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100, 1)
            : 0;

        // Monthly bookings chart (last 6 months)
        $bookingChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $bookingChart[] = [
                'month' => $month->format('M Y'),
                'count' => Booking::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)->count(),
                'revenue' => Payment::where('status', 'completed')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)->sum('amount'),
            ];
        }

        // Recent bookings
        $recentBookings = Booking::with(['user', 'venue'])
            ->latest()->limit(8)->get();

        // Recent users
        $recentUsers = User::where('role', 'user')
            ->latest()->limit(6)->get();

        // Top venues
        $topVenues = Venue::with('category')
            ->where('status', 'active')
            ->orderByDesc('bookings_count')
            ->limit(5)->get();

        // Booking status distribution
        $bookingStatus = [
            'pending'   => Booking::where('status', 'pending')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'completed' => Booking::where('status', 'completed')->count(),
            'cancelled' => Booking::where('status', 'cancelled')->count(),
        ];

        return view('admin.dashboard.index', compact(
            'stats', 'bookingChart', 'recentBookings',
            'recentUsers', 'topVenues', 'bookingStatus'
        ));
    }
}
