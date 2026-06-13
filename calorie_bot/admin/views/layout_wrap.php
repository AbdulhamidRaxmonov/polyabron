<?php
// Wraps $content into full HTML layout
$pageTitle = $pageTitle ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — iCalCalorie Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
  :root { --sidebar-w: 240px; }
  body  { background: #f5f6fa; font-family: 'Segoe UI', sans-serif; }

  /* Sidebar */
  .sidebar {
    width: var(--sidebar-w);
    min-height: 100vh;
    background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
    position: fixed; top: 0; left: 0; z-index: 100;
    padding-top: 0;
  }
  .sidebar-brand {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.1);
  }
  .sidebar-brand span { font-size:1.3rem; font-weight:700; color:#fff; }
  .sidebar-brand small { color: rgba(255,255,255,.5); font-size:.75rem; }

  .nav-section {
    font-size:.65rem; font-weight:700; letter-spacing:1.5px;
    color:rgba(255,255,255,.35); padding:.75rem 1.5rem .25rem;
    text-transform:uppercase;
  }
  .nav-link {
    color: rgba(255,255,255,.7) !important;
    padding: .5rem 1.5rem;
    border-radius:8px;
    margin: 1px 8px;
    font-size:.88rem;
    transition: all .15s;
  }
  .nav-link:hover, .nav-link.active {
    background: rgba(255,255,255,.12) !important;
    color: #fff !important;
  }
  .nav-link.active { background: rgba(99,102,241,.5) !important; }

  /* Main */
  .main-content { margin-left: var(--sidebar-w); min-height: 100vh; }
  .topbar {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: .75rem 1.5rem;
    position: sticky; top:0; z-index:99;
  }

  .card { border-radius:14px; }
  .table th { font-size:.78rem; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; }

  @media(max-width:768px){
    .sidebar { transform: translateX(-100%); }
    .main-content { margin-left:0; }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar d-flex flex-column">
  <div class="sidebar-brand">
    <span>🥗 iCalCalorie</span><br>
    <small>Admin Panel</small>
  </div>
  <div class="mt-2 flex-grow-1">
    <div class="nav-section">Asosiy</div>
    <a href="index.php"     class="nav-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">📊 Dashboard</a>
    <a href="users.php"     class="nav-link <?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>">👥 Foydalanuvchilar</a>
    <a href="diary.php"     class="nav-link <?= basename($_SERVER['PHP_SELF'])==='diary.php'?'active':'' ?>">📋 Kundalik</a>

    <div class="nav-section">Kontent</div>
    <a href="foods.php"     class="nav-link <?= basename($_SERVER['PHP_SELF'])==='foods.php'?'active':'' ?>">🍽 Taomlar</a>
    <a href="broadcast.php" class="nav-link <?= basename($_SERVER['PHP_SELF'])==='broadcast.php'?'active':'' ?>">📢 Xabar yuborish</a>
  </div>
  <div class="p-3">
    <a href="logout.php" class="nav-link text-danger opacity-75">🚪 Chiqish</a>
  </div>
</nav>

<!-- Main -->
<div class="main-content">
  <!-- Topbar -->
  <div class="topbar d-flex align-items-center justify-content-between">
    <h6 class="mb-0 fw-semibold text-secondary"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h6>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small">📅 <?= date('d.m.Y') ?></span>
      <span class="badge bg-primary">Admin</span>
    </div>
  </div>

  <!-- Content -->
  <div class="p-4">
    <?= $content ?? '' ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
