<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// --- Search & Filter ---
$search   = sanitize($_GET['search'] ?? '');
$location = sanitize($_GET['location'] ?? '');
$type     = sanitize($_GET['type'] ?? '');
$priceMin = (int)($_GET['price_min'] ?? 0);
$priceMax = (int)($_GET['price_max'] ?? 0);

$where = ["l.status = 'approved'"];
$params = [];

if ($search) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ? OR l.location LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($location) { $where[] = "l.location LIKE ?"; $params[] = "%$location%"; }
if ($type)     { $where[] = "l.type = ?";          $params[] = $type; }
if ($priceMin) { $where[] = "l.price >= ?";         $params[] = $priceMin; }
if ($priceMax) { $where[] = "l.price <= ?";         $params[] = $priceMax; }

$sql = "SELECT l.*, o.name AS owner_name, o.phone AS owner_phone,
               (SELECT image_path FROM listing_images WHERE listing_id = l.id ORDER BY is_primary DESC LIMIT 1) AS thumb,
               (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE listing_id = l.id) AS avg_rating,
               (SELECT COUNT(id) FROM reviews WHERE listing_id = l.id) AS reviews_count
        FROM listings l
        JOIN owners o ON l.owner_id = o.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

// Distinct locations for filter dropdown
$locs = $pdo->query("SELECT DISTINCT location FROM listings WHERE status='approved' AND location IS NOT NULL ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Browse Properties';
require_once 'includes/header.php';
?>

<!-- FILTER BAR -->
<section class="filter-section">
  <form class="filter-container" method="GET" action="<?= BASE_URL ?>/browse.php">
    <span class="filter-label"><i class="fas fa-filter"></i> Filter</span>

    <select name="location" class="filter-input">
      <option value="">📍 All Locations</option>
      <?php foreach ($locs as $loc): ?>
        <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= htmlspecialchars($loc) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="type" class="filter-input">
      <option value="">🏘 All Types</option>
      <option value="apartment" <?= $type==='apartment'?'selected':'' ?>>Apartment</option>
      <option value="house"     <?= $type==='house'    ?'selected':'' ?>>House</option>
      <option value="room"      <?= $type==='room'     ?'selected':'' ?>>Room</option>
      <option value="commercial"<?= $type==='commercial'?'selected':'' ?>>Commercial</option>
      <option value="land"      <?= $type==='land'     ?'selected':'' ?>>Land</option>
    </select>

    <input type="number" name="price_min" class="filter-input" placeholder="Min Price (Rs.)" value="<?= $priceMin ?: '' ?>" style="min-width:130px;">
    <input type="number" name="price_max" class="filter-input" placeholder="Max Price (Rs.)" value="<?= $priceMax ?: '' ?>" style="min-width:130px;">

    <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Apply</button>
    <?php if ($search || $location || $type || $priceMin || $priceMax): ?>
      <a href="<?= BASE_URL ?>/browse.php" class="filter-clear"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
    <span class="results-count"><?= count($listings) ?> <?= count($listings) === 1 ? 'property' : 'properties' ?> found</span>
  </form>
</section>

<!-- LISTINGS GRID -->
<div class="container">
  <div class="section-header">
    <div>
      <h2 class="section-title">
        <?= ($search || $location || $type || $priceMin || $priceMax) ? 'Search Results' : 'Browse All Properties' ?>
      </h2>
      <p class="section-subtitle">Click any property to view real-time availability and owner contacts.</p>
    </div>
  </div>

  <?php if (empty($listings)): ?>
    <div class="no-results">
      <i class="fas fa-search-location"></i>
      <h3>No properties found</h3>
      <p>Try adjusting your search or filters.</p>
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
