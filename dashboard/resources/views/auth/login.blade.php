<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirish | Oynaa Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .login-card {
            background: rgba(255,255,255,.97);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .brand-circle {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: 700; color: #fff;
            margin: 0 auto 1rem;
        }
        .form-control {
            border-radius: 12px; padding: .75rem 1rem;
            border: 2px solid #e9ecef;
            transition: border-color .2s;
        }
        .form-control:focus { border-color: #6c5ce7; box-shadow: 0 0 0 3px rgba(108,92,231,.15); }
        .btn-login {
            background: linear-gradient(135deg, #6c5ce7, #a29bfe);
            border: none; border-radius: 12px;
            padding: .8rem; font-weight: 600;
            color: #fff; width: 100%;
            transition: opacity .2s;
        }
        .btn-login:hover { opacity: .9; color: #fff; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="brand-circle">O</div>
    <h4 class="text-center fw-700 mb-1">Oynaa Admin</h4>
    <p class="text-center text-muted mb-4 small">Boshqaruv paneliga kirish</p>

    @if($errors->any())
        <div class="alert alert-danger rounded-3 small py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-600 small">Email</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-2" style="border-radius:12px 0 0 12px">
                    <i class="bi bi-envelope text-muted"></i>
                </span>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="form-control" placeholder="admin@oynaa.uz" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-600 small">Parol</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-2" style="border-radius:12px 0 0 12px">
                    <i class="bi bi-lock text-muted"></i>
                </span>
                <input type="password" name="password"
                       class="form-control" placeholder="••••••••" required>
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Eslab qol</label>
            </div>
        </div>
        <button type="submit" class="btn btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Kirish
        </button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
