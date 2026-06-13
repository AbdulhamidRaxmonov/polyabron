<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\Config;
Config::load();
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'] ?? '';
    $adminPass = $_ENV['ADMIN_PASSWORD'] ?? 'admin123';
    if (hash_equals($adminPass, $pass)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php'); exit;
    }
    $error = 'Parol noto\'g\'ri!';
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Kirish — iCalCalorie</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
  body { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); min-height:100vh;
         display:flex; align-items:center; justify-content:center; }
  .card { border-radius:20px; border:none; box-shadow:0 20px 60px rgba(0,0,0,.2); }
</style>
</head>
<body>
<div class="card p-5" style="width:360px">
  <div class="text-center mb-4">
    <div style="font-size:3rem">🥗</div>
    <h4 class="fw-bold mt-2">iCalCalorie</h4>
    <p class="text-muted small">Admin Panel</p>
  </div>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label fw-semibold">Parol</label>
      <input type="password" name="password" class="form-control form-control-lg"
             placeholder="••••••••" autofocus required>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill">Kirish</button>
  </form>
</div>
</body>
</html>
