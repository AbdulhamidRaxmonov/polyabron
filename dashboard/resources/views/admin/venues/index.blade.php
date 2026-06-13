@extends('admin.layouts.app')

@section('title', 'Maydonlar')
@section('page_title', 'Maydonlar boshqaruvi')

@section('content')

{{-- Filter bar --}}
<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Nomi bo'yicha qidirish...">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Barcha holat</option>
                @foreach(['pending','active','inactive','rejected'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">Barcha kategoriya</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name_uz }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary rounded-pill px-3">
                <i class="bi bi-search"></i> Qidirish
            </button>
            <a href="{{ route('admin.venues.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </form>
</div>

{{-- Pending Alert --}}
@if($venues->where('status','pending')->count() > 0)
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <span>{{ $venues->where('status','pending')->count() }} ta maydon tasdiqlashingizni kutmoqda.</span>
</div>
@endif

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Maydon</th>
                        <th>Ega</th>
                        <th>Kategoriya</th>
                        <th>Narx/soat</th>
                        <th>Reyting</th>
                        <th>Holat</th>
                        <th>Tavsiya</th>
                        <th class="pe-3">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($venues as $venue)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                @if($venue->cover_image)
                                    <img src="{{ asset('storage/'.$venue->cover_image) }}"
                                         class="rounded-2" width="44" height="44" style="object-fit:cover">
                                @else
                                    <div class="rounded-2 bg-light d-flex align-items-center justify-content-center"
                                         style="width:44px;height:44px">
                                        <i class="bi bi-geo-alt text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.venues.show', $venue->id) }}"
                                       class="fw-600 text-decoration-none text-dark">
                                        {{ $venue->name }}
                                    </a>
                                    <br><small class="text-muted">{{ Str::limit($venue->address, 30) }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $venue->owner->name ?? '—' }}</td>
                        <td>{{ $venue->category->name_uz ?? '—' }}</td>
                        <td>{{ number_format($venue->price_per_hour) }} so'm</td>
                        <td>
                            <i class="bi bi-star-fill text-warning"></i>
                            {{ number_format($venue->rating, 1) }}
                            <small class="text-muted">({{ $venue->reviews_count }})</small>
                        </td>
                        <td>
                            <span class="badge badge-{{ $venue->status }} px-2 py-1 rounded-pill">
                                {{ ucfirst($venue->status) }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.venues.featured', $venue->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm border-0 p-0">
                                    <i class="bi bi-star{{ $venue->is_featured ? '-fill text-warning' : ' text-muted' }} fs-5"></i>
                                </button>
                            </form>
                        </td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.venues.show', $venue->id) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill px-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($venue->status === 'pending')
                                    <form method="POST" action="{{ route('admin.venues.approve', $venue->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-2" title="Tasdiqlash">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.venues.reject', $venue->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2" title="Rad etish">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.venues.edit', $venue->id) }}"
                                   class="btn btn-sm btn-outline-secondary rounded-pill px-2">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.venues.destroy', $venue->id) }}"
                                      onsubmit="return confirm('Maydonni o\'chirishni tasdiqlaysizmi?')">
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
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-geo-alt fs-2 d-block mb-2"></i>
                            Maydonlar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($venues->hasPages())
    <div class="card-footer bg-white border-0">
        {{ $venues->links() }}
    </div>
    @endif
</div>

@endsection
