@extends('admin.layouts.app')
@section('title', 'Izohlar')
@section('page_title', 'Izohlar boshqaruvi')

@section('content')

<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Maydon nomi...">
        </div>
        <div class="col-md-2">
            <select name="rating" class="form-select">
                <option value="">Barcha reyting</option>
                @for($i=5; $i>=1; $i--)
                    <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                        {{ $i }} yulduz
                    </option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <select name="visible" class="form-select">
                <option value="">Barcha</option>
                <option value="1" {{ request('visible') === '1' ? 'selected' : '' }}>Ko'rinadigan</option>
                <option value="0" {{ request('visible') === '0' ? 'selected' : '' }}>Yashirilgan</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary rounded-pill px-3">
                <i class="bi bi-search"></i>
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
                        <th class="ps-3">Foydalanuvchi</th>
                        <th>Maydon</th>
                        <th>Reyting</th>
                        <th>Izoh</th>
                        <th>Holat</th>
                        <th>Sana</th>
                        <th class="pe-3">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-600">{{ $review->user->name ?? '—' }}</div>
                            <small class="text-muted">{{ $review->user->phone ?? '' }}</small>
                        </td>
                        <td>{{ $review->venue->name ?? '—' }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                @for($i=1; $i<=5; $i++)
                                    <i class="bi bi-star{{ $i <= $review->rating ? '-fill text-warning' : ' text-muted' }}"
                                       style="font-size:.8rem"></i>
                                @endfor
                                <span class="ms-1 small fw-600">{{ $review->rating }}</span>
                            </div>
                        </td>
                        <td>
                            <span title="{{ $review->comment }}">{{ Str::limit($review->comment, 50) ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $review->is_visible ? 'badge-active' : 'badge-inactive' }} px-2 py-1 rounded-pill">
                                {{ $review->is_visible ? 'Ko\'rinadigan' : 'Yashirilgan' }}
                            </span>
                        </td>
                        <td>{{ $review->created_at->format('d.m.Y') }}</td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <form method="POST" action="{{ route('admin.reviews.toggle', $review->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm {{ $review->is_visible ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill px-2"
                                            title="{{ $review->is_visible ? 'Yashirish' : 'Ko\'rsatish' }}">
                                        <i class="bi bi-eye{{ $review->is_visible ? '-slash' : '' }}"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review->id) }}"
                                      onsubmit="return confirm('Izohni o\'chirishni tasdiqlaysizmi?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-star fs-2 d-block mb-2"></i>Izohlar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reviews->hasPages())
    <div class="card-footer bg-white border-0">{{ $reviews->links() }}</div>
    @endif
</div>

@endsection
