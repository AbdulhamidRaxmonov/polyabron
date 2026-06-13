<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\{Config, Database, TelegramBot};
Config::load();
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text    = trim($_POST['message'] ?? '');
    $target  = $_POST['target'] ?? 'all';
    $preview = isset($_POST['preview']);

    if ($text && !$preview) {
        $bot = new TelegramBot();

        $where = "WHERE is_blocked = 0";
        if ($target === 'active_week') {
            $where .= " AND id IN (SELECT DISTINCT user_id FROM diary_entries WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))";
        } elseif ($target === 'setup_done') {
            $where .= " AND is_setup_done = 1";
        }

        $users = Database::fetchAll("SELECT telegram_id FROM users $where");
        $sent = $failed = 0;

        foreach ($users as $u) {
            $r = $bot->sendMessage((int)$u['telegram_id'], "📢 " . $text);
            ($r['ok'] ?? false) ? $sent++ : $failed++;
            usleep(35000); // ~28 msg/s
        }

        $result = ['sent' => $sent, 'failed' => $failed, 'total' => count($users)];
    }
}

$totalUsers  = Database::fetchColumn("SELECT COUNT(*) FROM users WHERE is_blocked=0");
$activeWeek  = Database::fetchColumn("SELECT COUNT(DISTINCT user_id) FROM diary_entries WHERE entry_date >= DATE_SUB(CURDATE(),INTERVAL 7 DAY)");
$setupDone   = Database::fetchColumn("SELECT COUNT(*) FROM users WHERE is_setup_done=1 AND is_blocked=0");

ob_start(); ?>
<h5 class="fw-bold mb-4">📢 Xabar yuborish (Broadcast)</h5>

<?php if ($result): ?>
<div class="alert alert-success">
  ✅ Yuborildi! Muvaffaqiyatli: <b><?= $result['sent'] ?></b>,
  Xato: <b><?= $result['failed'] ?></b>,
  Jami: <b><?= $result['total'] ?></b>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">Xabar matni (HTML qo'llaniladi)</label>
            <textarea name="message" class="form-control font-monospace" rows="8"
                      placeholder="Xabar yozing...&#10;&#10;<b>Qalin</b>, <i>kursiv</i>, <code>kod</code>"><?=
              htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            <div class="form-text">Telegram HTML parse mode qo'llaniladi.</div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Kimga yuborish</label>
            <select name="target" class="form-select">
              <option value="all">👥 Barcha foydalanuvchilar (<?= $totalUsers ?>)</option>
              <option value="setup_done">✅ Profil sozlagan (<?= $setupDone ?>)</option>
              <option value="active_week">🔥 Bu hafta faol (<?= $activeWeek ?>)</option>
            </select>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="preview" value="1" class="btn btn-outline-secondary">👁 Ko'rish</button>
            <button type="submit" class="btn btn-danger px-5"
                    onclick="return confirm('Haqiqatan ham yuborasizmi?')">📢 Yuborish</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if (!empty($_POST['message']) && isset($_POST['preview'])): ?>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold bg-white">📱 Ko'rinish</div>
      <div class="card-body">
        <div class="p-3 bg-light rounded" style="font-family:sans-serif;font-size:14px">
          📢 <?= nl2br(htmlspecialchars($_POST['message'])) ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/views/layout_wrap.php'; ?>
