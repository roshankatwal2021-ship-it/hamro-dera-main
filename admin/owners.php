<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireAdminLogin();

// Actions
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $pdo->prepare("UPDATE owners SET status='approved' WHERE id=?")->execute([(int)$_GET['approve']]);
    flashMessage('success', 'Owner approved successfully.');
    redirect(BASE_URL . '/admin/owners.php');
}
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $pdo->prepare("UPDATE owners SET status='rejected' WHERE id=?")->execute([(int)$_GET['reject']]);
    flashMessage('success', 'Owner rejected.');
    redirect(BASE_URL . '/admin/owners.php');
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Cascade deletes listings & images (FK), but also delete image files
    $listingIds = $pdo->prepare("SELECT id FROM listings WHERE owner_id=?");
    $listingIds->execute([(int)$_GET['delete']]);
    foreach ($listingIds->fetchAll() as $lid) {
        $imgs = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id=?");
        $imgs->execute([$lid['id']]);
        foreach ($imgs->fetchAll() as $img) {
            $fp = dirname(__DIR__) . '/' . $img['image_path'];
            if (file_exists($fp)) unlink($fp);
        }
    }
    $pdo->prepare("DELETE FROM owners WHERE id=?")->execute([(int)$_GET['delete']]);
    flashMessage('success', 'Owner and all their listings deleted.');
    redirect(BASE_URL . '/admin/owners.php');
}

$filter = sanitize($_GET['filter'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$where  = []; $params = [];
if ($filter) { $where[] = "status=?"; $params[] = $filter; }
if ($search)  { $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql = "SELECT *, (SELECT COUNT(*) FROM listings WHERE owner_id=owners.id) AS listing_count FROM owners" . ($where ? ' WHERE '.implode(' AND ',$where) : '') . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$owners = $stmt->fetchAll();

$pageTitle = 'Manage Owners';
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
      <li><a href="<?= BASE_URL ?>/admin/owners.php" class="active"><i class="fas fa-users"></i> Manage Owners</a></li>
      <li><a href="<?= BASE_URL ?>/admin/listings.php"><i class="fas fa-home"></i> Manage Listings</a></li>
      <li><a href="<?= BASE_URL ?>/"><i class="fas fa-globe"></i> View Site</a></li>
      <li class="logout-link"><a href="<?= BASE_URL ?>/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>
  <main class="dash-main">
    <div class="page-hdr">
      <div><h1>Manage Owners</h1><p>Approve, reject or delete owner accounts</p></div>
    </div>
    <?= renderFlash() ?>

    <!-- Filter bar -->
    <form method="GET" style="display:flex;gap:.7rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center;">
      <input type="text" name="search" class="filter-input" placeholder="Search name / email…" value="<?= htmlspecialchars($search) ?>" style="min-width:220px;">
      <select name="filter" class="filter-input">
        <option value="">All Status</option>
        <option value="pending"  <?= $filter==='pending' ?'selected':'' ?>>Pending</option>
        <option value="approved" <?= $filter==='approved'?'selected':'' ?>>Approved</option>
        <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option>
      </select>
      <button type="submit" class="filter-btn"><i class="fas fa-search"></i> Filter</button>
      <?php if ($filter || $search): ?><a href="<?= BASE_URL ?>/admin/owners.php" class="filter-clear"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
    </form>

    <div class="table-card">
      <div class="table-hdr">
        <h3>Owners (<?= count($owners) ?>)</h3>
      </div>
      <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Listings</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($owners)): ?>
            <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--muted)">No owners found.</td></tr>
          <?php else: foreach ($owners as $o): ?>
            <tr>
              <td class="td-main"><?= htmlspecialchars($o['name']) ?></td>
              <td><?= htmlspecialchars($o['email']) ?></td>
              <td><?= htmlspecialchars($o['phone']) ?></td>
              <td style="text-align:center"><?= $o['listing_count'] ?></td>
              <td><span class="badge badge-<?= $o['status'] ?>"><?= $o['status'] ?></span></td>
              <td style="font-size:.82rem"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              <td class="actions">
                <?php if ($o['status'] !== 'approved'): ?>
                  <a href="?approve=<?= $o['id'] ?>" class="btn btn-success btn-sm btn-icon" title="Approve"><i class="fas fa-check"></i></a>
                <?php endif; ?>
                <?php if ($o['status'] !== 'rejected'): ?>
                  <a href="?reject=<?= $o['id'] ?>" class="btn btn-warning btn-sm btn-icon" title="Reject" data-confirm="Reject this owner?"><i class="fas fa-ban"></i></a>
                <?php endif; ?>
                <a href="?delete=<?= $o['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Delete" data-confirm="Delete this owner AND all their listings permanently?"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
