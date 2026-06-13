@extends('admin.layouts.app')

@section('title', 'Foydalanuvchilar')
@section('page_title', 'Foydalanuvchilar boshqaruvi')

@section('content')

{{-- Stats --}}
<div class="row g-3 mb-4">
    @foreach([
        ['label'=>'Jami', 'value'=>$stats['total'], 'color'=>'primary', 'icon'=>'people-fill'],
        ['label'=>'Foydalanuvchi', 'value'=>$stats['users'], 'color'=>'success', 'icon'=>'person-fill'],
        ['label'=>'Maydon egasi', 'value'=>$stats['owners'], 'color'=>'info', 'icon'=>'building'],
        ['label'=>'Admin', 'value'=>$stats['admins'], 'color'=>'warning', 'icon'=>'shield-fill'],
        ['label'=>'Bloklangan', 'value'=>$stats['blocked'], 'color'=>'danger', 'icon'=>'slash-circle-fill'],
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

{{-- Filter --}}
<div class="form-card mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Ism, telefon, email...">
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select">
                <option value="">Barcha rol</option>
                <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>Foydalanuvchi</option>
                <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Ega</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Barcha holat</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Faol</option>
                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Bloklangan</option>
            </select>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-pill px-3">
                <i class="bi bi-search"></i>
            </button>
            <a href="{{ route('admin.users.create') }}" class="btn btn-success rounded-pill px-3">
                <i class="bi bi-person-plus"></i> Yangi
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
                        <th class="ps-3">Foydalanuvchi</th>
                        <th>Telefon</th>
                        <th>Rol</th>
                        <th>Balans</th>
                        <th>Bronlar</th>
                        <th>Holat</th>
                        <th>Ro'yxatdan o'tgan</th>
                        <th class="pe-3">Amallar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-700"
                                     style="width:38px;height:38px;font-size:.9rem">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.users.show', $user->id) }}"
                                       class="fw-600 text-decoration-none text-dark">
                                        {{ $user->name }}
                                    </a>
                                    <br><small class="text-muted">{{ $user->email ?? '—' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->phone }}</td>
                        <td>
                            <span class="badge rounded-pill
                                {{ $user->role === 'admin' ? 'bg-danger' : ($user->role === 'owner' ? 'bg-info text-dark' : 'bg-secondary') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td>{{ number_format($user->balance) }} so'm</td>
                        <td>{{ $user->bookings_count ?? 0 }}</td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }} px-2 py-1 rounded-pill">
                                {{ $user->is_active ? 'Faol' : 'Bloklangan' }}
                            </span>
                        </td>
                        <td>{{ $user->created_at->format('d.m.Y') }}</td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.users.show', $user->id) }}"
                                   class="btn btn-sm btn-outline-primary rounded-pill px-2">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user->id) }}"
                                   class="btn btn-sm btn-outline-secondary rounded-pill px-2">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.users.block', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill px-2"
                                            title="{{ $user->is_active ? 'Bloklash' : 'Faollashtirish' }}">
                                        <i class="bi bi-{{ $user->is_active ? 'slash-circle' : 'check-circle' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-2 d-block mb-2"></i>Foydalanuvchilar topilmadi
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-white border-0">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection
