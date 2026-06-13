@extends('admin.layouts.app')

@section('title', 'Bronlar')
@section('page_title', 'Bronlar boshqaruvi')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'Jami', 'value'=>$stats['total'], 'color'=>'primary', 'icon'=>'calendar-check-fill'],
        ['label'=>'Kutmoqda', 'value'=>$stats['pending'], 'color'=>'warning', 'icon'=>'clock-fill'],
        ['label'=>'Tasdiqlangan', 'value'=>$stats['confirmed'], 'color'=>'info', 'icon'=>'check-circle-fill'],
        ['label'=>'Yakunlangan', 'value'=>$stats['completed'], 'color'=>'success', 'icon'=>'trophy-fill'],
        ['label'=>'Bekor qilingan', 'value'=>$stats['cancelled'], 'color'=>'danger', 'icon'=>'x-circle-fill'],
    ] as $s)
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="stat-icon bg-{{ $s['color'] }} bg-opacity-10 text-{{ $s['color'] }}">
                    <i class="bi bi-{{ $s['icon'] }}"></i>
                </div>
                <div>
                    <p class="text-muted mb-0" style="font-size:.75rem">{{ $s['label'] }}</p>
                    <h5 class="fw-700 mb-0">{{ number_format($s['value']) }}</h5>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filters --}}
<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Bron raqami yoki ism...">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Barcha holat</option>
                @foreach(['pending','confirmed','completed','cancelled','no_show'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="payment" class="form-select">
                <option value="">To'lov holati</option>
                @foreach(['unpaid','paid','refunded'] as $s)
                    <option value="{{ $s }}" {{ request('payment') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date" value="{{ request('date') }}" class="form-control">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary rounded-pill px-3">
                <i class="bi bi-search"></i>
            </button>
            <a href="{{ route('admin.bookings.export') }}?{{ http_build_query(request()->all()) }}"
               class="btn btn-outline-success rounded-pill px-3">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Bron raqami</th>
                        <th>Foydalanuvchi</th>
                        <th>Maydon</th>
                        <th>Sana & Vaqt</th>
                        <th>Summa</th>
                        <th>To'lov</th>
                        <th>Holat</th>
                        <th class="pe-3">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                    <tr>
                        <td class="ps-3">
                            <a href="{{ route('admin.bookings.show', $booking->id) }}"
                               class="fw-600 text-decoration-none text-primary">
                                {{ $booking->booking_number }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('admin.users.show', $booking->user_id) }}"
                               class="text-decoration-none text-dark">
                                {{ $booking->user->name ?? '—' }}
                            </a>
                            <br><small class="text-muted">{{ $booking->user->phone ?? '' }}</small>
                        </td>
                        <td>
                            <a href="{{ route('admin.venues.show', $booking->venue_id) }}"
                               class="text-decoration-none text-dark">
                                {{ Str::limit($booking->venue->name ?? '—', 25) }}
                            </a>
                        </td>
                        <td>
                            {{ $booking->booking_date->format('d.m.Y') }}
                            <br><small class="text-muted">
                                {{ substr($booking->start_time,0,5) }} – {{ substr($booking->end_time,0,5) }}
                            </small>
                        </td>
                        <td>{{ number_format($booking->final_amount) }} so'm</td>
                        <td>
                            @if($booking->payment_method)
                                <span class="badge bg-{{ $booking->payment_method === 'payme' ? 'primary' : ($booking->payment_method === 'click' ? 'success' : 'secondary') }}">
                                    {{ strtoupper($booking->payment_method) }}
                                </span>
                            @endif
                            <span class="badge badge-{{ $booking->payment_status === 'paid' ? 'completed' : ($booking->payment_status === 'unpaid' ? 'pending' : 'cancelled') }} px-2 rounded-pill">
                                {{ ucfirst($booking->payment_status) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $booking->status }} px-2 py-1 rounded-pill">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                        <td class="pe-3">
                            <a href="{{ route('admin.bookings.show', $booking->id) }}"
                               class="btn btn-sm btn-outline-primary rounded-pill px-2">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Bronlar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
    <div class="card-footer bg-white border-0">
        {{ $bookings->links() }}
    </div>
    @endif
</div>

@endsection
