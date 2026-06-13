<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\{Config, Database};
Config::load();
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $tid    = (int)($_POST['telegram_id'] ?? 0);

    if ($action === 'block' && $tid) {
        Database::execute("UPDATE users SET is_blocked = NOT is_blocked WHERE telegram_id = ?", [$tid]);
    }
    if ($action === 'delete' && $tid) {
        Database::execute("DELETE FROM users WHERE telegram_id = ?", [$tid]);
    }
    header('Location: users.php'); exit;
}

// Filters
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 25;
$offset = ($page - 1) * $perPage;

$where  = $search
    ? "WHERE (first_name LIKE ? OR username LIKE ? OR telegram_id LIKE ?)"
    : "";
$params = $search ? ["%$search%", "%$search%", "%$search%"] : [];

$total = Database::fetchColumn("SELECT COUNT(*) FROM users $where", $params);
$users = Database::fetchAll(
    "SELECT u.*, 
       (SELECT COUNT(*) FROM diary_entries d WHERE d.user_id=u.id) as entry_count,
       (SELECT current_streak FROM user_streaks s WHERE s.user_id=u.id) as streak
     FROM users u $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset",
    $params
);
$pages = (int)ceil($total / $perPage);

ob_start(); ?>
<div class="d-flex align-items-center justify-content-between mb-4">
  <h5 class="fw-bold mb-0">👥 Foydalanuvchilar</h5>
  <span class="badge bg-primary fs-6"><?= number_format($total) ?> ta</span>
</div>

<!-- Search -->
<form class="mb-3 d-flex gap-2" method="GET">
  <input type="text" name="search" class="form-control" placeholder="🔍 Ism, username yoki ID..."
         value="<?= htmlspecialchars($search) ?>">
  <button class="btn btn-primary px-4">Qidirish</button>
  <?php if($search): ?><a href="users.php" class="btn btn-outline-secondary">✕</a><?php endif; ?>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>ID</th><th>Ism</th><th>Username</th><th>Kaloriya</th>
          <th>Vazn</th><th>Maqsad</th><th>Yozuvlar</th>
          <th>🔥 Seriya</th><th>Ro'yxat</th><th>Holat</th><th>Amal</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr class="<?= $u['is_blocked'] ? 'table-danger' : '' ?>">
          <td><code class="small"><?= $u['telegram_id'] ?></code></td>
          <td><?= htmlspecialchars(trim($u['first_name'].' '.($u['last_name']??''))) ?></td>
          <td class="text-muted small"><?= $u['username'] ? '@'.htmlspecialchars($u['username']) : '—' ?></td>
          <td><?= $u['calorie_goal'] ?></td>
          <td><?= $u['weight_kg'] ? $u['weight_kg'].' кг' : '—' ?></td>
          <td><?= match($u['goal']){
            'lose'=>'📉','gain'=>'📈',default=>'⚖️'} ?></td>
          <td class="text-center"><?= $u['entry_count'] ?></td>
          <td class="text-center"><?= $u['streak'] ?? 0 ?>🔥</td>
          <td class="small text-muted"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if($u['is_blocked']): ?>
              <span class="badge bg-danger">Bloklangan</span>
            <?php elseif($u['is_setup_done']): ?>
              <span class="badge bg-success">Faol</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Yangi</span>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" class="d-flex gap-1">
              <input type="hidden" name="telegram_id" value="<?= $u['telegram_id'] ?>">
              <button name="action" value="block" class="btn btn-sm <?= $u['is_blocked']?'btn-success':'btn-outline-warning' ?>"
                title="<?= $u['is_blocked']?'Blokni olib tashlash':'Bloklash' ?>">
                <?= $u['is_blocked'] ? '✅' : '🚫' ?>
              </button>
              <button name="action" value="delete" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('O\'chirasizmi?')" title="O'chirish">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($users)): ?>
        <tr><td colspan="11" class="text-center py-4 text-muted">Foydalanuvchi topilmadi</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Pagination -->
<?php if($pages > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php for($p=1;$p<=$pages;$p++): ?>
    <li class="page-item <?= $p===$page?'active':'' ?>">
      <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php $content = ob_get_clean(); include __DIR__ . '/views/layout_wrap.php'; ?>
