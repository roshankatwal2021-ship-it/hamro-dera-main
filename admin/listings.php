<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdminLogin();

// Actions
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $pdo->prepare("UPDATE listings SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
    flashMessage('success', 'Listing approved and is now live!');
    redirect(BASE_URL . '/admin/listings.php');
}
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $pdo->prepare("UPDATE listings SET status='rejected' WHERE id=?")->execute([(int)$_GET['reject']]);
    flashMessage('success', 'Listing rejected.');
    redirect(BASE_URL . '/admin/listings.php');
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $lid = (int)$_GET['delete'];
    $imgs = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=?");
    $imgs->execute([$lid]);
    foreach ($imgs->fetchAll() as $img) {
        $fp = dirname(__DIR__) . '/' . $img['image_path'];
        if (file_exists($fp)) unlink($fp);
    }
    $pdo->prepare("DELETE FROM listings WHERE id=?")->execute([$lid]);
    flashMessage('success', 'Listing deleted permanently.');
    redirect(BASE_URL . '/admin/listings.php');
}

$filter = sanitize($_GET['filter'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$where  = []; $params = [];
if ($filter) { $where[] = "l.status=?"; $params[] = $filter; }
if ($search)  { $where[] = "(l.title LIKE ? OR l.location LIKE ? OR o.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql = "SELECT l.*, o.name AS owner_name, (SELECT image_path FROM listing_images WHERE listing_id=l.id ORDER BY is_primary DESC LIMIT 1) AS thumb
        FROM listings l JOIN owners o ON o.id=l.owner_id" . ($where ? ' WHERE '.implode(' AND ',$where) : '') . " ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$listings = $stmt->fetchAll();

$pageTitle = 'Manage Listings';
require_once '../includes/header.php';
?>
<div class="dash-layout">
  <aside class="sidebar">
    <div class="sidebar-head">
      <h3><?= htmlspecialchars($_SESSION['admin_name']) ?></h3>
      <p style="color:var(--accent);font-size:.78rem;font-weight:600;">⚡ Administrator</p>
    </div>
    <ul class="sidebar-nav">
      <li><a href="<?= BASE_URL ?>/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
      <li><a href="<?= BASE_URL ?>/admin/owners.php"><i class="fas fa-users"></i> Manage Owners</a></li>
      <li><a href="<?= BASE_URL ?>/admin/listings.php" class="active"><i class="fas fa-home"></i> Manage Listings</a></li>
      <li><a href="<?= BASE_URL ?>/"><i class="fas fa-globe"></i> View Site</a></li>
      <li class="logout-link"><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>
  <main class="dash-main">
    <div class="page-hdr">
      <div><h1>Manage Listings</h1><p>Review and moderate all property listings</p></div>
    </div>
    <?= renderFlash() ?>

    <!-- Filter bar -->
    <form method="GET" style="display:flex;gap:.7rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center;">
      <input type="text" name="search" class="filter-input" placeholder="Search title / location / owner…" value="<?= htmlspecialchars($search) ?>" style="min-width:240px;">
      <select name="filter" class="filter-input">
        <option value="">All Status</option>
        <option value="pending"  <?= $filter==='pending' ?'selected':'' ?>>Pending</option>
        <option value="approved" <?= $filter==='approved'?'selected':'' ?>>Approved</option>
        <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option>
      </select>
      <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Filter</button>
      <?php if ($filter || $search): ?><a href="<?= BASE_URL ?>/admin/listings.php" class="filter-clear"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
    </form>

    <div class="table-card">
      <div class="table-hdr"><h3>Listings (<?= count($listings) ?>)</h3></div>
      <table class="data-table">
        <thead><tr><th>Photo</th><th>Title</th><th>Owner</th><th>Location</th><th>Type</th><th>Price</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($listings)): ?>
            <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--muted)">No listings found.</td></tr>
          <?php else: foreach ($listings as $l): ?>
            <tr>
              <td><?php if($l['thumb']): ?><img src="<?= BASE_URL.'/'.$l['thumb'] ?>" class="td-img"><?php else: ?>—<?php endif; ?></td>
              <td class="td-main"><?= htmlspecialchars($l['title']) ?></td>
              <td><?= htmlspecialchars($l['owner_name']) ?></td>
              <td><?= htmlspecialchars($l['location']) ?></td>
              <td><?= typeLabel($l['type']) ?></td>
              <td><?= formatPrice($l['price']) ?></td>
              <td><span class="badge badge-<?= $l['status'] ?>"><?= $l['status'] ?></span></td>
              <td style="font-size:.82rem;white-space:nowrap"><?= date('M j, Y', strtotime($l['created_at'])) ?></td>
              <td class="actions">
                <?php if ($l['status'] !== 'approved'): ?>
                  <a href="?approve=<?= $l['id'] ?>" class="btn btn-success btn-sm btn-icon" title="Approve"><i class="fas fa-check"></i></a>
                <?php endif; ?>
                <?php if ($l['status'] !== 'rejected'): ?>
                  <a href="?reject=<?= $l['id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Reject" data-confirm="Reject this listing?"><i class="fas fa-ban"></i></a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/listing.php?id=<?= $l['id'] ?>" target="_blank" class="btn btn-outline btn-sm btn-icon" title="Preview" <?= $l['status']!=='approved'?'style="opacity:.4;pointer-events:none"':'' ?>><i class="fas fa-eye"></i></a>
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
