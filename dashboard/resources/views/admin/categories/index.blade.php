@extends('admin.layouts.app')

@section('title', 'Kategoriyalar')
@section('page_title', 'Kategoriyalar boshqaruvi')

@section('content')

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-lg me-1"></i> Yangi kategoriya
    </a>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:60px">№</th>
                        <th>Nomi (UZ)</th>
                        <th>Nomi (RU)</th>
                        <th>Maydonlar</th>
                        <th>Tartib</th>
                        <th>Holat</th>
                        <th class="pe-3">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                    <tr>
                        <td class="ps-3">
                            @if($cat->icon)
                                <img src="{{ asset('storage/'.$cat->icon) }}" width="32" height="32"
                                     class="rounded-2" style="object-fit:cover">
                            @else
                                <div class="rounded-2 bg-light d-flex align-items-center justify-content-center"
                                     style="width:32px;height:32px">
                                    <i class="bi bi-tags text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td class="fw-600">{{ $cat->name_uz }}</td>
                        <td>{{ $cat->name_ru }}</td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $cat->venues_count ?? 0 }} ta</span>
                        </td>
                        <td>{{ $cat->sort_order }}</td>
                        <td>
                            <span class="badge {{ $cat->is_active ? 'badge-active' : 'badge-inactive' }} px-2 py-1 rounded-pill">
                                {{ $cat->is_active ? 'Faol' : 'Nofaol' }}
                            </span>
                        </td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.categories.edit', $cat->id) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill px-2">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $cat->id) }}"
                                      onsubmit="return confirm('O\'chirishni tasdiqlaysizmi?')">
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
                            <i class="bi bi-tags fs-2 d-block mb-2"></i>Kategoriyalar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
