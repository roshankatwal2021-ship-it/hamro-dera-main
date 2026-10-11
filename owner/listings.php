<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireOwnerLogin();

$ownerId = $_SESSION['owner_id'];

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $check = $pdo->prepare("SELECT id FROM listings WHERE id = ? AND owner_id = ?");
    $check->execute([$delId, $ownerId]);
    if ($check->fetch()) {
        // Delete images from disk
        $imgs = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id = ?");
        $imgs->execute([$delId]);
        foreach ($imgs->fetchAll() as $img) {
            $fp = dirname(__DIR__) . '/' . $img['image_path'];
            if (file_exists($fp)) unlink($fp);
        }
        $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$delId]);
        flashMessage('success', 'Listing deleted successfully.');
    }
    redirect(BASE_URL . '/owner/listings.php');
}

$stmt = $pdo->prepare("SELECT l.*, (SELECT image_path FROM listing_images WHERE listing_id=l.id ORDER BY is_primary DESC LIMIT 1) AS thumb FROM listings l WHERE l.owner_id = ? ORDER BY l.created_at DESC");
$stmt->execute([$ownerId]);
$listings = $stmt->fetchAll();

$pageTitle = 'My Listings';
require_once '../includes/header.php';
?>
<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-head">
      <div class="owner-av" style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--purple));display:flex;align-items:center;justify-content:center;font-weight:800;margin-bottom:.5rem">
        <?= strtoupper(mb_substr($_SESSION['owner_name'], 0, 1)) ?>
      </div>
      <h3><?= htmlspecialchars($_SESSION['owner_name']) ?></h3>
      <p>Property Owner</p>
    </div>
    <ul class="sidebar-nav">
      <li><a href="<?= BASE_URL ?>/owner/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="<?= BASE_URL ?>/owner/listings.php" class="active"><i class="fas fa-list"></i> My Listings</a></li>
      <li><a href="<?= BASE_URL ?>/owner/add-listing.php"><i class="fas fa-plus-circle"></i> Add Listing</a></li>
      <li><a href="<?= BASE_URL ?>/"><i class="fas fa-globe"></i> View Site</a></li>
      <li class="logout-link"><a href="<?= BASE_URL ?>/owner/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>
  <main class="dash-main">
    <div class="page-hdr">
      <div><h1>My Listings</h1><p>Manage all your property listings</p></div>
      <a href="<?= BASE_URL ?>/owner/add-listing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Listing</a>
    </div>
    <?= renderFlash() ?>
    <div class="table-card">
      <div class="table-hdr">
        <h3>All Listings (<?= count($listings) ?>)</h3>
      </div>
      <table class="data-table">
        <thead><tr><th>Photo</th><th>Title</th><th>Location</th><th>Type</th><th>Price</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($listings)): ?>
            <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)">
              No listings yet. <a href="<?= BASE_URL ?>/owner/add-listing.php" style="color:var(--accent)">Add your first property!</a>
            </td></tr>
          <?php else: foreach ($listings as $l): ?>
            <tr>
              <td><?php if($l['thumb']): ?><img src="<?= BASE_URL.'/'.$l['thumb'] ?>" class="td-img"><?php else: ?>—<?php endif; ?></td>
              <td class="td-main"><?= htmlspecialchars($l['title']) ?></td>
              <td><?= htmlspecialchars($l['location']) ?></td>
              <td><?= typeLabel($l['type']) ?></td>
              <td><?= formatPrice($l['price']) ?></td>
              <td><span class="badge badge-<?= $l['status'] ?>"><?= $l['status'] ?></span></td>
              <td style="white-space:nowrap;font-size:.82rem"><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
              <td class="actions">
                <a href="<?= BASE_URL ?>/listing.php?id=<?= $l['id'] ?>" <?= $l['status']!=='approved'?'style="opacity:.4;pointer-events:none"':'' ?> class="btn btn-outline btn-sm btn-icon" title="View"><i class="fas fa-eye"></i></a>
                <a href="<?= BASE_URL ?>/owner/edit-listing.php?id=<?= $l['id'] ?>" class="btn btn-outline btn-sm btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                <a href="?delete=<?= $l['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Delete" data-confirm="Delete this listing permanently?"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
