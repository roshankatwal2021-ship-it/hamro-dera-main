<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireOwnerLogin();

$ownerId = $_SESSION['owner_id'];

// Stats
$total     = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE owner_id=?"); $total->execute([$ownerId]);
$pending   = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE owner_id=? AND status='pending'"); $pending->execute([$ownerId]);
$approved  = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE owner_id=? AND status='approved'"); $approved->execute([$ownerId]);
$rejected  = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE owner_id=? AND status='rejected'"); $rejected->execute([$ownerId]);

$totalN    = $total->fetchColumn();
$pendingN  = $pending->fetchColumn();
$approvedN = $approved->fetchColumn();
$rejectedN = $rejected->fetchColumn();

// Recent listings
$recent = $pdo->prepare("SELECT l.*, (SELECT image_path FROM listing_images WHERE listing_id=l.id LIMIT 1) AS thumb FROM listings l WHERE l.owner_id=? ORDER BY l.created_at DESC LIMIT 5");
$recent->execute([$ownerId]);
$recentListings = $recent->fetchAll();

$pageTitle = 'Owner Dashboard';
require_once '../includes/header.php';
?>
<div class="dash-layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-head">
      <div class="owner-av" style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-weight:800;margin-bottom:.5rem">
        <?= strtoupper(mb_substr($_SESSION['owner_name'], 0, 1)) ?>
      </div>
      <h3><?= htmlspecialchars($_SESSION['owner_name']) ?></h3>
      <p>Property Owner</p>
    </div>
    <ul class="sidebar-nav">
      <li><a href="<?= BASE_URL ?>/owner/index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="<?= BASE_URL ?>/owner/listings.php"><i class="fas fa-list"></i> My Listings</a></li>
      <li><a href="<?= BASE_URL ?>/owner/add-listing.php"><i class="fas fa-plus-circle"></i> Add Listing</a></li>
      <li><a href="<?= BASE_URL ?>/"><i class="fas fa-globe"></i> View Site</a></li>
      <li class="logout-link"><a href="<?= BASE_URL ?>/owner/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <!-- Main Content -->
  <main class="dash-main">
    <div class="page-hdr">
      <div>
        <h1>Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['owner_name']) ?>!</p>
      </div>
      <a href="<?= BASE_URL ?>/owner/add-listing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Listing</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon si-blue"><i class="fas fa-home"></i></div>
        <div class="stat-info"><h3 data-count="<?= $totalN ?>"><?= $totalN ?></h3><p>Total Listings</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3 data-count="<?= $approvedN ?>"><?= $approvedN ?></h3><p>Approved</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-yellow"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><h3 data-count="<?= $pendingN ?>"><?= $pendingN ?></h3><p>Pending Review</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-red"><i class="fas fa-times-circle"></i></div>
        <div class="stat-info"><h3 data-count="<?= $rejectedN ?>"><?= $rejectedN ?></h3><p>Rejected</p></div>
      </div>
    </div>

    <!-- Recent Listings -->
    <div class="table-card">
      <div class="table-hdr">
        <h3>Recent Listings</h3>
        <a href="<?= BASE_URL ?>/owner/listings.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <table class="data-table">
        <thead><tr>
          <th>Photo</th><th>Title</th><th>Location</th><th>Price</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php if (empty($recentListings)): ?>
            <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--muted)">No listings yet. <a href="<?= BASE_URL ?>/owner/add-listing.php" style="color:var(--accent)">Add your first one!</a></td></tr>
          <?php else: ?>
            <?php foreach ($recentListings as $l): ?>
            <tr>
              <td><?php if($l['thumb']): ?><img src="<?= BASE_URL.'/'.$l['thumb'] ?>" class="td-img" alt=""><?php else: ?>—<?php endif; ?></td>
              <td class="td-main"><?= htmlspecialchars($l['title']) ?></td>
              <td><?= htmlspecialchars($l['location']) ?></td>
              <td><?= formatPrice($l['price']) ?></td>
              <td><span class="badge badge-<?= $l['status'] ?>"><?= $l['status'] ?></span></td>
              <td class="actions">
                <a href="<?= BASE_URL ?>/owner/edit-listing.php?id=<?= $l['id'] ?>" class="btn btn-outline btn-sm btn-icon"><i class="fas fa-edit"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Info box -->
    <?php if ($pendingN > 0): ?>
    <div class="alert alert-info" style="margin-top:1.5rem">
      <i class="fas fa-info-circle"></i>
      You have <?= $pendingN ?> listing(s) awaiting admin approval. They will appear publicly once approved.
    </div>
    <?php endif; ?>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
