<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdminLogin();

// Fetch stats
$totalOwners    = $pdo->query("SELECT COUNT(*) FROM owners")->fetchColumn();
$pendingOwners  = $pdo->query("SELECT COUNT(*) FROM owners WHERE status='pending'")->fetchColumn();
$approvedOwners = $pdo->query("SELECT COUNT(*) FROM owners WHERE status='approved'")->fetchColumn();
$totalListings  = $pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$pendingListings= $pdo->query("SELECT COUNT(*) FROM listings WHERE status='pending'")->fetchColumn();
$approvedListings=$pdo->query("SELECT COUNT(*) FROM listings WHERE status='approved'")->fetchColumn();

// Recent pending items
$pendingOwnersList = $pdo->query("SELECT * FROM owners WHERE status='pending' ORDER BY created_at DESC LIMIT 5")->fetchAll();
$pendingListingsList= $pdo->query("SELECT l.*,o.name AS owner_name FROM listings l JOIN owners o ON o.id=l.owner_id WHERE l.status='pending' ORDER BY l.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>
<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-head">
      <h3><?= htmlspecialchars($_SESSION['admin_name']) ?></h3>
      <p style="color:var(--accent);font-size:.78rem;font-weight:600;">⚡ Administrator</p>
    </div>
    <ul class="sidebar-nav">
      <li><a href="<?= BASE_URL ?>/admin/index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="<?= BASE_URL ?>/admin/owners.php"><i class="fas fa-users"></i> Manage Owners</a>
        <?php if ($pendingOwners > 0): ?><span class="badge badge-pending" style="margin-left:auto;font-size:.65rem;padding:.15rem .5rem"><?= $pendingOwners ?></span><?php endif; ?>
      </li>
      <li><a href="<?= BASE_URL ?>/admin/listings.php"><i class="fas fa-home"></i> Manage Listings</a>
        <?php if ($pendingListings > 0): ?><span class="badge badge-pending" style="margin-left:auto;font-size:.65rem;padding:.15rem .5rem"><?= $pendingListings ?></span><?php endif; ?>
      </li>
      <li><a href="<?= BASE_URL ?>/"><i class="fas fa-globe"></i> View Site</a></li>
      <li class="logout-link"><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>
  <main class="dash-main">
    <div class="page-hdr">
      <div><h1>Admin Dashboard</h1><p>Platform overview and pending approvals</p></div>
    </div>
    <?= renderFlash() ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon si-blue"><i class="fas fa-users"></i></div>
        <div class="stat-info"><h3 data-count="<?= $totalOwners ?>"><?= $totalOwners ?></h3><p>Total Owners</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info"><h3 data-count="<?= $approvedOwners ?>"><?= $approvedOwners ?></h3><p>Approved Owners</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-yellow"><i class="fas fa-user-clock"></i></div>
        <div class="stat-info"><h3 data-count="<?= $pendingOwners ?>"><?= $pendingOwners ?></h3><p>Pending Owners</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-purple"><i class="fas fa-home"></i></div>
        <div class="stat-info"><h3 data-count="<?= $totalListings ?>"><?= $totalListings ?></h3><p>Total Listings</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><h3 data-count="<?= $approvedListings ?>"><?= $approvedListings ?></h3><p>Live Listings</p></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon si-red"><i class="fas fa-clock"></i></div>
        <div class="stat-info"><h3 data-count="<?= $pendingListings ?>"><?= $pendingListings ?></h3><p>Pending Listings</p></div>
      </div>
    </div>

    <!-- Pending Owners -->
    <?php if (!empty($pendingOwnersList)): ?>
    <div class="table-card" style="margin-bottom:1.5rem">
      <div class="table-hdr">
        <h3>⏳ Pending Owner Approvals</h3>
        <a href="<?= BASE_URL ?>/admin/owners.php" class="btn btn-outline btn-sm">View All Owners</a>
      </div>
      <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($pendingOwnersList as $o): ?>
          <tr>
            <td class="td-main"><?= htmlspecialchars($o['name']) ?></td>
            <td><?= htmlspecialchars($o['email']) ?></td>
            <td><?= htmlspecialchars($o['phone']) ?></td>
            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
            <td class="actions">
              <a href="<?= BASE_URL ?>/admin/owners.php?approve=<?= $o['id'] ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
              <a href="<?= BASE_URL ?>/admin/owners.php?reject=<?= $o['id'] ?>"  class="btn btn-danger btn-sm" data-confirm="Reject this owner?"><i class="fas fa-times"></i> Reject</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <!-- Pending Listings -->
    <?php if (!empty($pendingListingsList)): ?>
    <div class="table-card">
      <div class="table-hdr">
        <h3>⏳ Pending Listing Approvals</h3>
        <a href="<?= BASE_URL ?>/admin/listings.php" class="btn btn-outline btn-sm">View All Listings</a>
      </div>
      <table class="data-table">
        <thead><tr><th>Title</th><th>Owner</th><th>Location</th><th>Price</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($pendingListingsList as $l): ?>
          <tr>
            <td class="td-main"><?= htmlspecialchars($l['title']) ?></td>
            <td><?= htmlspecialchars($l['owner_name']) ?></td>
            <td><?= htmlspecialchars($l['location']) ?></td>
            <td><?= formatPrice($l['price']) ?></td>
            <td class="actions">
              <a href="<?= BASE_URL ?>/admin/listings.php?approve=<?= $l['id'] ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</a>
              <a href="<?= BASE_URL ?>/admin/listings.php?reject=<?= $l['id'] ?>"  class="btn btn-danger btn-sm" data-confirm="Reject this listing?"><i class="fas fa-times"></i> Reject</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php if (empty($pendingOwnersList) && empty($pendingListingsList)): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> All caught up! No pending approvals.</div>
    <?php endif; ?>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
