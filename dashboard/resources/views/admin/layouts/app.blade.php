<!DOCTYPE html>
<html lang="uz" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Oynaa Admin</title>

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --sidebar-width: 260px;
            --primary: #6c5ce7;
            --primary-dark: #5a4fd1;
            --sidebar-bg: #1a1a2e;
            --sidebar-text: #a8b2d8;
            --sidebar-active: #6c5ce7;
        }

        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1040;
            transition: transform .3s ease;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }

        .sidebar-brand .brand-logo {
            width: 36px; height: 36px;
            background: var(--primary);
            border-radius: 10px;
            display: inline-flex;
            align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 1.1rem;
        }

        .sidebar-brand .brand-name {
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            margin-left: .5rem;
        }

        .sidebar-menu { padding: .8rem 0; }

        .sidebar-section {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(168,178,216,.5);
            padding: .8rem 1.4rem .3rem;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .6rem 1.4rem;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 8px;
            margin: 2px 8px;
            transition: all .2s;
            font-size: .9rem;
        }

        .sidebar-item:hover,
        .sidebar-item.active {
            background: rgba(108,92,231,.2);
            color: #fff;
        }

        .sidebar-item.active { background: var(--primary); color: #fff; }
        .sidebar-item .badge { margin-left: auto; }

        /* Main */
        .main-wrapper { margin-left: var(--sidebar-width); min-height: 100vh; }

        /* Topbar */
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: .75rem 1.5rem;
            position: sticky; top: 0; z-index: 999;
            display: flex; align-items: center; justify-content: space-between;
        }

        .topbar .page-title { font-size: 1.1rem; font-weight: 600; color: #343a40; }

        /* Cards */
        .stat-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }

        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        /* Table */
        .table-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }

        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
            border-bottom: 2px solid #e9ecef;
        }

        /* Status badges */
        .badge-pending    { background: #fff3cd; color: #856404; }
        .badge-confirmed  { background: #d1ecf1; color: #0c5460; }
        .badge-completed  { background: #d4edda; color: #155724; }
        .badge-cancelled  { background: #f8d7da; color: #721c24; }
        .badge-active     { background: #d4edda; color: #155724; }
        .badge-inactive   { background: #e2e3e5; color: #383d41; }
        .badge-rejected   { background: #f8d7da; color: #721c24; }

        /* Form card */
        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
            margin-bottom: 1.5rem;
        }

        /* Alert */
        .alert { border-radius: 12px; border: none; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand d-flex align-items-center">
        <span class="brand-logo">O</span>
        <span class="brand-name">Oynaa</span>
    </div>

    <div class="sidebar-menu">
        <span class="sidebar-section">Asosiy</span>

        <a href="{{ route('admin.dashboard') }}"
           class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <span class="sidebar-section">Boshqaruv</span>

        <a href="{{ route('admin.venues.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.venues.*') ? 'active' : '' }}">
            <i class="bi bi-geo-alt-fill"></i> Maydonlar
            @php $pendingVenues = \App\Models\Venue::where('status','pending')->count(); @endphp
            @if($pendingVenues > 0)
                <span class="badge bg-warning text-dark">{{ $pendingVenues }}</span>
            @endif
        </a>

        <a href="{{ route('admin.bookings.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
            <i class="bi bi-calendar-check-fill"></i> Bronlar
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Foydalanuvchilar
        </a>

        <a href="{{ route('admin.payments.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card-fill"></i> To'lovlar
        </a>

        <a href="{{ route('admin.reviews.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
            <i class="bi bi-star-fill"></i> Izohlar
            @php $pendingReviews = \App\Models\Review::where('is_visible',false)->count(); @endphp
            @if($pendingReviews > 0)
                <span class="badge bg-danger">{{ $pendingReviews }}</span>
            @endif
        </a>

        <span class="sidebar-section">Sozlamalar</span>

        <a href="{{ route('admin.categories.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
            <i class="bi bi-tags-fill"></i> Kategoriyalar
        </a>

        <a href="{{ route('admin.banners.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
            <i class="bi bi-image-fill"></i> Bannerlar
        </a>

        <div class="mt-4 p-3">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="sidebar-item w-100 border-0 bg-transparent text-start">
                    <i class="bi bi-box-arrow-left"></i> Chiqish
                </button>
            </form>
        </div>
    </div>
</nav>

<!-- Main -->
<div class="main-wrapper">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="page-title">@yield('page_title', 'Dashboard')</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
                <i class="bi bi-bell fs-5 text-secondary"></i>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white"
                         style="width:32px;height:32px;font-size:.8rem">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <span class="d-none d-md-inline text-dark fw-500">{{ Auth::user()->name }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="bi bi-box-arrow-left me-2"></i>Chiqish
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar toggle
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('show');
    });
</script>
@stack('scripts')
</body>
</html>
