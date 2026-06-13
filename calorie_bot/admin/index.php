<?php
/**
 * Admin Panel — Dashboard
 */
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\{Config, Database};
use App\Features\Statistics;
Config::load();
session_start();

// ── Auth ──────────────────────────────────────────────────────────────────
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

$stats = Statistics::globalStats();
$newUsersWeek = Database::fetchAll(
    "SELECT DATE(created_at) as day, COUNT(*) as cnt
     FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY day ORDER BY day"
);
$topFoods = Database::fetchAll(
    "SELECT food_name, COUNT(*) as cnt FROM diary_entries
     GROUP BY food_name ORDER BY cnt DESC LIMIT 10"
);
$recentUsers = Database::fetchAll(
    "SELECT * FROM users ORDER BY created_at DESC LIMIT 10"
);

include __DIR__ . '/views/layout.php';
?>
<?php ob_start(); ?>

<div class="row g-4 mb-4">
  <?php
  $cards = [
    ['🧑‍🤝‍🧑', 'Jami foydalanuvchilar', number_format($stats['total_users']),   'primary'],
    ['📅',       'Bugun faol',             number_format($stats['active_today']),  'success'],
    ['📆',       'Hafta faol',             number_format($stats['active_week']),   'info'],
    ['📝',       'Jami yozuvlar',          number_format($stats['total_entries']), 'warning'],
    ['🍽',       'Taomlar bazasi',         number_format($stats['total_foods']),   'secondary'],
    ['💧',       'Jami suv (л)',           number_format($stats['total_water_l']), 'primary'],
  ];
  foreach ($cards as [$icon,$label,$value,$color]): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center p-3">
        <div class="display-6 mb-1"><?= $icon ?></div>
        <h4 class="fw-bold mb-0 text-<?= $color ?>"><?= $value ?></h4>
        <small class="text-muted"><?= $label ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- New users chart -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">📈 Yangi foydalanuvchilar (7 kun)</div>
      <div class="card-body"><canvas id="usersChart" height="100"></canvas></div>
    </div>
  </div>

  <!-- Top foods -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">🏆 Top 10 taom</div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($topFoods as $i => $f): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
            <span><?= $i+1 ?>. <?= htmlspecialchars($f['food_name']) ?></span>
            <span class="badge bg-primary rounded-pill"><?= $f['cnt'] ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Recent users -->
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">👥 So'nggi foydalanuvchilar</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th><th>Ism</th><th>Username</th>
                <th>Maqsad</th><th>Kaloriya</th><th>Ro'yxat</th><th>Holat</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($recentUsers as $u): ?>
              <tr>
                <td><code><?= $u['telegram_id'] ?></code></td>
                <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= $u['username'] ? '@'.htmlspecialchars($u['username']) : '—' ?></td>
                <td><?= match($u['goal']){
                  'lose'=>'📉 Yo\'qotish','gain'=>'📈 Olish',default=>'⚖️ Saqlash'} ?></td>
                <td><?= $u['calorie_goal'] ?> ккал</td>
                <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if($u['is_blocked']): ?>
                    <span class="badge bg-danger">Bloklangan</span>
                  <?php elseif($u['is_setup_done']): ?>
                    <span class="badge bg-success">Faol</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Sozlanmagan</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const ctx = document.getElementById('usersChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($newUsersWeek, 'day')) ?>,
    datasets: [{
      label: 'Yangi foydalanuvchilar',
      data: <?= json_encode(array_column($newUsersWeek, 'cnt')) ?>,
      backgroundColor: 'rgba(99,102,241,0.7)',
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});
</script>

<?php $content = ob_get_clean(); include __DIR__ . '/views/layout_wrap.php'; ?>
