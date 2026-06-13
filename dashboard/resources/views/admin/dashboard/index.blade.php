@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

{{-- ── Overview Stats ──────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Jami foydalanuvchilar</p>
                        <h3 class="fw-700 mb-0">{{ number_format($stats['total_users']) }}</h3>
                        <small class="text-success"><i class="bi bi-people"></i> {{ $stats['total_owners'] }} ega</small>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Faol maydonlar</p>
                        <h3 class="fw-700 mb-0">{{ number_format($stats['total_venues']) }}</h3>
                        @if($stats['pending_venues'] > 0)
                            <small class="text-warning"><i class="bi bi-clock"></i> {{ $stats['pending_venues'] }} kutmoqda</small>
                        @else
                            <small class="text-muted">Hammasi tasdiqlangan</small>
                        @endif
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Bugungi bronlar</p>
                        <h3 class="fw-700 mb-0">{{ $stats['today_bookings'] }}</h3>
                        <small class="text-muted">Jami: {{ number_format($stats['total_bookings']) }}</small>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-xl-3">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">Bu oy daromad</p>
                        <h3 class="fw-700 mb-0">{{ number_format($stats['month_revenue']) }}
                            <small class="fs-6">so'm</small>
                        </h3>
                        <small class="{{ $stats['revenue_growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                            <i class="bi bi-arrow-{{ $stats['revenue_growth'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($stats['revenue_growth']) }}% o'tgan oyga nisbatan
                        </small>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── Charts Row ──────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card table-card h-100">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-600 mb-0">So'nggi 6 oy statistikasi</h6>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-600 mb-0">Bronlar holati</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- ── Tables Row ──────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <!-- Recent Bookings -->
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3 pb-0">
                <h6 class="fw-600 mb-0">So'nggi bronlar</h6>
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                    Barchasi <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Bron raqami</th>
                                <th>Foydalanuvchi</th>
                                <th>Maydon</th>
                                <th>Sana</th>
                                <th>Summa</th>
                                <th>Holat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBookings as $booking)
                            <tr>
                                <td class="ps-3">
                                    <a href="{{ route('admin.bookings.show', $booking->id) }}"
                                       class="text-decoration-none fw-600 text-primary">
                                        {{ $booking->booking_number }}
                                    </a>
                                </td>
                                <td>{{ $booking->user->name ?? '—' }}</td>
                                <td>{{ Str::limit($booking->venue->name ?? '—', 20) }}</td>
                                <td>{{ $booking->booking_date->format('d.m.Y') }}</td>
                                <td>{{ number_format($booking->final_amount) }} so'm</td>
                                <td>
                                    <span class="badge badge-{{ $booking->status }} px-2 py-1 rounded-pill">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Venues -->
    <div class="col-lg-4">
        <div class="card table-card">
            <div class="card-header bg-white border-0 pt-3 pb-0">
                <h6 class="fw-600 mb-0">Top maydonlar</h6>
            </div>
            <div class="card-body">
                @foreach($topVenues as $i => $venue)
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="fw-700 text-muted" style="width:20px">{{ $i+1 }}</span>
                    @if($venue->cover_image)
                        <img src="{{ asset('storage/'.$venue->cover_image) }}" class="rounded-2" width="40" height="40" style="object-fit:cover">
                    @else
                        <div class="rounded-2 bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                            <i class="bi bi-geo-alt text-muted"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1 min-width-0">
                        <p class="mb-0 fw-600 small text-truncate">{{ $venue->name }}</p>
                        <small class="text-muted">{{ $venue->category->name_uz ?? '' }}</small>
                    </div>
                    <span class="badge bg-light text-dark">{{ $venue->bookings_count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Revenue & Bookings Chart
const ctx1 = document.getElementById('revenueChart').getContext('2d');
const bookingData = @json($bookingChart);

new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: bookingData.map(d => d.month),
        datasets: [
            {
                label: 'Daromad (so\'m)',
                data: bookingData.map(d => d.revenue),
                backgroundColor: 'rgba(108, 92, 231, 0.8)',
                borderRadius: 8,
                yAxisID: 'y',
            },
            {
                label: 'Bronlar soni',
                data: bookingData.map(d => d.count),
                backgroundColor: 'rgba(162, 155, 254, 0.4)',
                borderRadius: 8,
                yAxisID: 'y1',
                type: 'line',
                borderColor: '#a29bfe',
                tension: .4,
                fill: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y:  { position: 'left',  grid: { display: false } },
            y1: { position: 'right', grid: { display: false } },
        }
    }
});

// Status Pie Chart
const ctx2 = document.getElementById('statusChart').getContext('2d');
const statusData = @json($bookingStatus);

new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Kutmoqda', 'Tasdiqlangan', 'Yakunlangan', 'Bekor qilingan'],
        datasets: [{
            data: [statusData.pending, statusData.confirmed, statusData.completed, statusData.cancelled],
            backgroundColor: ['#ffc107', '#0dcaf0', '#198754', '#dc3545'],
            borderWidth: 3,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15, font: { size: 11 } } }
        }
    }
});
</script>
@endpush
