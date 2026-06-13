@extends('admin.layouts.app')
@section('title', 'Bannerlar')
@section('page_title', 'Bannerlar boshqaruvi')

@section('content')

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.banners.create') }}" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-lg me-1"></i> Yangi banner
    </a>
</div>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Rasm</th>
                        <th>Sarlavha</th>
                        <th>Turi</th>
                        <th>Tartib</th>
                        <th>Holat</th>
                        <th>Muddati</th>
                        <th class="pe-3">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banners as $banner)
                    <tr>
                        <td class="ps-3">
                            <img src="{{ asset('storage/'.$banner->image) }}"
                                 class="rounded-2" width="80" height="45" style="object-fit:cover">
                        </td>
                        <td>{{ $banner->title_uz ?? '—' }}</td>
                        <td>
                            <span class="badge bg-secondary rounded-pill">{{ ucfirst($banner->type) }}</span>
                        </td>
                        <td>{{ $banner->sort_order }}</td>
                        <td>
                            <span class="badge {{ $banner->is_active ? 'badge-active' : 'badge-inactive' }} px-2 py-1 rounded-pill">
                                {{ $banner->is_active ? 'Faol' : 'Nofaol' }}
                            </span>
                        </td>
                        <td>
                            @if($banner->ends_at)
                                <small class="text-muted">{{ $banner->ends_at->format('d.m.Y') }}</small>
                            @else
                                <small class="text-muted">Muddatsiz</small>
                            @endif
                        </td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.banners.edit', $banner->id) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill px-2">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}"
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
                            <i class="bi bi-image fs-2 d-block mb-2"></i>Bannerlar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($banners->hasPages())
    <div class="card-footer bg-white border-0">{{ $banners->links() }}</div>
    @endif
</div>
@endsection
