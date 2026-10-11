<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$ownerId = (int)($_GET['id'] ?? 0);
if (!$ownerId) { redirect(BASE_URL . '/'); }

$ownerStmt = $pdo->prepare("SELECT name, phone, created_at FROM owners WHERE id = ? AND status = 'approved'");
$ownerStmt->execute([$ownerId]);
$ownerInfo = $ownerStmt->fetch();

if (!$ownerInfo) {
    header('HTTP/1.0 404 Not Found');
    require_once 'includes/header.php';
    echo '<div class="container" style="padding:5rem 1rem;text-align:center;">
        <h2 style="color:var(--text2)">Owner not found</h2>
        <a href="' . BASE_URL . '/" class="btn btn-primary" style="display:inline-flex;margin-top:1rem">← Back to Browse</a>
    </div>';
    require_once 'includes/footer.php';
    exit;
}

$sql = "SELECT l.*, o.name AS owner_name, o.phone AS owner_phone,
               (SELECT image_path FROM listing_images WHERE listing_id = l.id ORDER BY is_primary DESC LIMIT 1) AS thumb,
               (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE listing_id = l.id) AS avg_rating,
               (SELECT COUNT(id) FROM reviews WHERE listing_id = l.id) AS reviews_count
        FROM listings l
        JOIN owners o ON l.owner_id = o.id
        WHERE l.owner_id = ? AND l.status = 'approved'
        ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$ownerId]);
$listings = $stmt->fetchAll();

$pageTitle = htmlspecialchars($ownerInfo['name']) . "'s Properties";
require_once 'includes/header.php';
?>

<div class="container">
  <div style="background:var(--bg2); border:1px solid var(--border); border-radius:var(--r-lg); padding:2rem; margin-bottom:2rem; display:flex; align-items:center; gap:1.5rem;">
    <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-size:1.8rem;font-weight:800;color:#fff;">
      <?= strtoupper(mb_substr($ownerInfo['name'], 0, 1)) ?>
    </div>
    <div>
      <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:0.3rem"><?= htmlspecialchars($ownerInfo['name']) ?></h1>
      <p style="color:var(--text2);font-size:0.9rem"><i class="fas fa-calendar-alt"></i> Joined <?= date('M Y', strtotime($ownerInfo['created_at'])) ?> &bull; <strong><?= count($listings) ?></strong> active properties</p>
    </div>
  </div>

  <div class="section-header">
    <div>
      <h2 class="section-title">Properties by <?= htmlspecialchars($ownerInfo['name']) ?></h2>
    </div>
  </div>

  <?php if (empty($listings)): ?>
    <div class="no-results">
      <i class="fas fa-home"></i>
      <h3>No approved properties</h3>
      <p><?= htmlspecialchars($ownerInfo['name']) ?> currently has no active properties listed.</p>
    </div>
  <?php else: ?>
    <div class="listings-grid">
      <?php foreach ($listings as $l): ?>
        <a href="<?= BASE_URL ?>/listing.php?id=<?= $l['id'] ?>" class="listing-card">
          <div class="card-image">
            <?php if ($l['thumb']): ?>
              <img src="<?= BASE_URL . '/' . htmlspecialchars($l['thumb']) ?>" alt="<?= htmlspecialchars($l['title']) ?>" loading="lazy">
            <?php else: ?>
              <div class="card-no-image"><i class="fas fa-home"></i></div>
            <?php endif; ?>
            <span class="card-type-badge"><?= typeLabel($l['type']) ?></span>
            <?php if ($l['avg_rating']): ?>
              <span class="card-rating-badge" style="position:absolute;top:10px;right:10px;background:rgba(0,0,0,0.65);backdrop-filter:blur(8px);color:var(--warning);padding:0.28rem 0.65rem;border-radius:50px;font-size:0.75rem;font-weight:700;border:1px solid rgba(255,255,255,0.1);"><i class="fas fa-star"></i> <?= $l['avg_rating'] ?> (<?= $l['reviews_count'] ?>)</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <h3 class="card-title"><?= htmlspecialchars($l['title']) ?></h3>
            <div class="card-location"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($l['location']) ?></div>
            <div class="card-footer">
              <div class="card-price">
                <?= formatPrice($l['price']) ?>
                <small>per month</small>
              </div>
              <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
