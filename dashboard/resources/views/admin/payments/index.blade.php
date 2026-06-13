@extends('admin.layouts.app')

@section('title', 'To\'lovlar')
@section('page_title', 'To\'lovlar boshqaruvi')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Jami daromad</p>
                <h4 class="fw-700 text-success mb-0">{{ number_format($stats['total']) }} so'm</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Bugungi daromad</p>
                <h4 class="fw-700 text-primary mb-0">{{ number_format($stats['today']) }} so'm</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body d-flex gap-3">
                <div>
                    <p class="text-muted small mb-1">Payme</p>
                    <h5 class="fw-700 mb-0">{{ number_format($stats['payme']) }}</h5>
                </div>
                <div>
                    <p class="text-muted small mb-1">Click</p>
                    <h5 class="fw-700 mb-0">{{ number_format($stats['click']) }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body">
                <p class="text-muted small mb-1">Jarayondagi to'lovlar</p>
                <h4 class="fw-700 text-warning mb-0">{{ $stats['pending_count'] }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <select name="provider" class="form-select">
                <option value="">Barcha tizim</option>
                <option value="payme" {{ request('provider') === 'payme' ? 'selected' : '' }}>Payme</option>
                <option value="click" {{ request('provider') === 'click' ? 'selected' : '' }}>Click</option>
                <option value="cash" {{ request('provider') === 'cash' ? 'selected' : '' }}>Naqd</option>
                <option value="balance" {{ request('provider') === 'balance' ? 'selected' : '' }}>Balans</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Barcha holat</option>
                @foreach(['pending','completed','failed','cancelled','refunded'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="from" value="{{ request('from') }}" class="form-control" placeholder="Boshlanish">
        </div>
        <div class="col-md-2">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control" placeholder="Tugash">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary rounded-pill px-3">
                <i class="bi bi-search"></i> Qidirish
            </button>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Tranzaksiya ID</th>
                        <th>Foydalanuvchi</th>
                        <th>Maydon</th>
                        <th>Tizim</th>
                        <th>Summa</th>
                        <th>Holat</th>
                        <th>Sana</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td class="ps-3">
                            <code class="small">{{ $payment->provider_transaction_id ?? $payment->id }}</code>
                        </td>
                        <td>{{ $payment->user->name ?? '—' }}</td>
                        <td>{{ $payment->booking->venue->name ?? '—' }}</td>
                        <td>
                            <span class="badge rounded-pill
                                {{ $payment->provider === 'payme' ? 'bg-primary' :
                                   ($payment->provider === 'click' ? 'bg-success' : 'bg-secondary') }}">
                                {{ strtoupper($payment->provider) }}
                            </span>
                        </td>
                        <td class="fw-600">{{ number_format($payment->amount) }} so'm</td>
                        <td>
                            <span class="badge badge-{{ $payment->status === 'completed' ? 'completed' : ($payment->status === 'pending' ? 'pending' : 'cancelled') }} px-2 py-1 rounded-pill">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td>{{ $payment->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-credit-card fs-2 d-block mb-2"></i>To'lovlar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
    <div class="card-footer bg-white border-0">
        {{ $payments->links() }}
    </div>
    @endif
</div>

@endsection
