<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
use App\Core\{Config, Database};
Config::load();
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';

// ── Handle POST actions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $required = ['name_ru','calories'];
        $valid = true;
        foreach ($required as $f) {
            if (empty($_POST[$f])) { $valid = false; break; }
        }
        if ($valid) {
            Database::insert('foods', [
                'name_ru'        => trim($_POST['name_ru']),
                'name_uz'        => trim($_POST['name_uz'] ?? ''),
                'name_en'        => trim($_POST['name_en'] ?? ''),
                'category'       => trim($_POST['category'] ?? 'other'),
                'calories'       => (float)$_POST['calories'],
                'protein_g'      => (float)($_POST['protein_g'] ?? 0),
                'fat_g'          => (float)($_POST['fat_g'] ?? 0),
                'carbs_g'        => (float)($_POST['carbs_g'] ?? 0),
                'fiber_g'        => (float)($_POST['fiber_g'] ?? 0),
                'serving_size_g' => (int)($_POST['serving_size_g'] ?? 100),
                'serving_name'   => trim($_POST['serving_name'] ?? '100г'),
                'is_liquid'      => isset($_POST['is_liquid']) ? 1 : 0,
                'is_verified'    => 1,
            ]);
            $message = '✅ Taom qo\'shildi!';
        } else {
            $message = '❌ Majburiy maydonlarni to\'ldiring.';
        }
    } elseif ($action === 'delete') {
        Database::execute("DELETE FROM foods WHERE id = ?", [(int)$_POST['food_id']]);
        $message = '🗑 Taom o\'chirildi.';
    } elseif ($action === 'verify') {
        Database::execute("UPDATE foods SET is_verified = 1 WHERE id = ?", [(int)$_POST['food_id']]);
        $message = '✅ Taom tasdiqlandi.';
    }
    header("Location: foods.php?msg=" . urlencode($message)); exit;
}
if (isset($_GET['msg'])) $message = $_GET['msg'];

$search   = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? '';
$verified = $_GET['verified'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 30;
$offset   = ($page - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($search)   { $where .= " AND (name_ru LIKE ? OR name_uz LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($category) { $where .= " AND category = ?"; $params[] = $category; }
if ($verified !== '') { $where .= " AND is_verified = ?"; $params[] = (int)$verified; }

$total = Database::fetchColumn("SELECT COUNT(*) FROM foods $where", $params);
$foods = Database::fetchAll("SELECT * FROM foods $where ORDER BY name_ru LIMIT $perPage OFFSET $offset", $params);
$pages = (int)ceil($total / $perPage);

$categories = Database::fetchAll("SELECT DISTINCT category FROM foods ORDER BY category");

ob_start(); ?>
<?php if($message): ?>
<div class="alert alert-info alert-dismissible fade show"><?= htmlspecialchars($message) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <h5 class="fw-bold mb-0">🍽 Taomlar bazasi</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFoodModal">+ Yangi taom</button>
</div>

<!-- Filters -->
<form class="row g-2 mb-3" method="GET">
  <div class="col-md-4">
    <input type="text" name="search" class="form-control form-control-sm"
           placeholder="🔍 Taom nomi..." value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-md-2">
    <select name="category" class="form-select form-select-sm">
      <option value="">Barcha kategoriya</option>
      <?php foreach($categories as $c): ?>
        <option value="<?= $c['category'] ?>" <?= $category===$c['category']?'selected':'' ?>>
          <?= ucfirst($c['category']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select name="verified" class="form-select form-select-sm">
      <option value="">Barcha holat</option>
      <option value="1" <?= $verified==='1'?'selected':'' ?>>✅ Tasdiqlangan</option>
      <option value="0" <?= $verified==='0'?'selected':'' ?>>⏳ Tasdiqlanmagan</option>
    </select>
  </div>
  <div class="col-auto">
    <button class="btn btn-primary btn-sm px-3">Qidirish</button>
    <a href="foods.php" class="btn btn-outline-secondary btn-sm">✕</a>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>Nomi (RU)</th><th>Nomi (UZ)</th><th>Kategoriya</th>
          <th>Kaloriya</th><th>Oqsil</th><th>Yog'</th><th>Karbo</th>
          <th>Porsiya</th><th>Holat</th><th>Amal</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach($foods as $f): ?>
        <tr>
          <td class="fw-semibold"><?= htmlspecialchars($f['name_ru']) ?></td>
          <td class="text-muted"><?= htmlspecialchars($f['name_uz'] ?? '') ?></td>
          <td><span class="badge bg-light text-dark border"><?= $f['category'] ?></span></td>
          <td class="text-danger fw-semibold"><?= $f['calories'] ?></td>
          <td><?= $f['protein_g'] ?></td>
          <td><?= $f['fat_g'] ?></td>
          <td><?= $f['carbs_g'] ?></td>
          <td><?= $f['serving_size_g'] ?>г (<?= htmlspecialchars($f['serving_name']) ?>)</td>
          <td><?= $f['is_verified']
              ? '<span class="badge bg-success">✅</span>'
              : '<span class="badge bg-warning text-dark">⏳</span>' ?></td>
          <td>
            <form method="POST" class="d-flex gap-1">
              <input type="hidden" name="food_id" value="<?= $f['id'] ?>">
              <?php if(!$f['is_verified']): ?>
                <button name="action" value="verify" class="btn btn-success btn-sm">✅</button>
              <?php endif; ?>
              <button name="action" value="delete" class="btn btn-outline-danger btn-sm"
                onclick="return confirm('O\'chirasizmi?')">🗑</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($pages > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center pagination-sm">
    <?php for($p=1;$p<=$pages;$p++): ?>
      <li class="page-item <?= $p===$page?'active':'' ?>">
        <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&verified=<?= urlencode($verified) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<!-- Add food modal -->
<div class="modal fade" id="addFoodModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title">➕ Yangi taom qo'shish</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Nomi (RU) *</label>
              <input type="text" name="name_ru" class="form-control" required placeholder="Картофель отварной">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Nomi (UZ)</label>
              <input type="text" name="name_uz" class="form-control" placeholder="Qaynatilgan kartoshka">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Kategoriya</label>
              <select name="category" class="form-select">
                <?php foreach(['vegetables','fruits','grains','meat','fish','eggs','dairy',
                               'nuts','legumes','sweets','drinks','fastfood','sauces','uzbek','healthy','other']
                  as $cat): ?>
                  <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Kaloriya * (100г)</label>
              <input type="number" step="0.1" name="calories" class="form-control" required min="0" max="900">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Oqsil (г)</label>
              <input type="number" step="0.1" name="protein_g" class="form-control" value="0" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Yog' (г)</label>
              <input type="number" step="0.1" name="fat_g" class="form-control" value="0" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Karbohidrat (г)</label>
              <input type="number" step="0.1" name="carbs_g" class="form-control" value="0" min="0">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Porsiya (г)</label>
              <input type="number" name="serving_size_g" class="form-control" value="100" min="1">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold">Porsiya nomi</label>
              <input type="text" name="serving_name" class="form-control" value="100г" placeholder="1 bo'lak">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_liquid" id="isLiquid">
                <label class="form-check-label" for="isLiquid">💧 Suyuqlik</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
          <button type="submit" class="btn btn-primary">Saqlash</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/views/layout_wrap.php'; ?>
