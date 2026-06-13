<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\{Config, Database};
Config::load();
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$date   = $_GET['date'] ?? date('Y-m-d');
$userId = (int)($_GET['user_id'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 50;
$offset = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($date)   { $where .= " AND d.entry_date = ?"; $params[] = $date; }
if ($userId) { $where .= " AND d.user_id = ?"; $params[] = $userId; }

$total   = Database::fetchColumn("SELECT COUNT(*) FROM diary_entries d $where", $params);
$entries = Database::fetchAll(
    "SELECT d.*, u.first_name, u.telegram_id, u.username
     FROM diary_entries d
     JOIN users u ON u.id = d.user_id
     $where ORDER BY d.entry_date DESC, d.entry_time DESC
     LIMIT $perPage OFFSET $offset",
    $params
);
$pages = (int)ceil($total / $perPage);

ob_start(); ?>
<h5 class="fw-bold mb-4">📋 Kundalik yozuvlar</h5>

<form class="row g-2 mb-3" method="GET">
  <div class="col-md-3">
    <input type="date" name="date" class="form-control form-control-sm"
           value="<?= htmlspecialchars($date) ?>">
  </div>
  <div class="col-md-3">
    <input type="number" name="user_id" class="form-control form-control-sm"
           placeholder="User DB ID" value="<?= $userId ?: '' ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-primary btn-sm">Filter</button>
    <a href="diary.php" class="btn btn-outline-secondary btn-sm">✕</a>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>Foydalanuvchi</th><th>Sana</th><th>Vaqt</th>
          <th>Ovqat turi</th><th>Taom</th><th>Miqdor</th>
          <th>Kaloriya</th><th>Oqsil</th><th>Yog'</th><th>Karbo</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($entries as $e): ?>
        <tr>
          <td>
            <small class="text-muted"><?= htmlspecialchars($e['first_name']) ?></small><br>
            <code class="small"><?= $e['telegram_id'] ?></code>
          </td>
          <td><?= $e['entry_date'] ?></td>
          <td class="text-muted"><?= substr($e['entry_time'],0,5) ?></td>
          <td><?= match($e['meal_type']){
            'breakfast'=>'🌅','lunch'=>'☀️','dinner'=>'🌙',default=>'🍎'} ?>
            <?= ucfirst($e['meal_type']) ?></td>
          <td class="fw-semibold"><?= htmlspecialchars($e['food_name']) ?></td>
          <td><?= round($e['amount_g']) ?>г</td>
          <td class="text-danger fw-semibold"><?= round($e['calories']) ?></td>
          <td><?= round($e['protein_g'],1) ?></td>
          <td><?= round($e['fat_g'],1) ?></td>
          <td><?= round($e['carbs_g'],1) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($entries)): ?>
        <tr><td colspan="10" class="text-center py-4 text-muted">Yozuvlar topilmadi</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if($pages > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center pagination-sm">
    <?php for($p=1;$p<=$pages;$p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>&date=<?= $date ?>&user_id=<?= $userId ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__ . '/views/layout_wrap.php'; ?>
