<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireOwnerLogin();

$ownerId   = $_SESSION['owner_id'];
$listingId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND owner_id = ?");
$stmt->execute([$listingId, $ownerId]);
$listing = $stmt->fetch();
if (!$listing) {
    flashMessage('error', 'Listing not found.');
    redirect(BASE_URL . '/owner/listings.php');
}

$errors  = [];
$images  = getListingImages($listingId, $pdo);

// Delete a specific image
if (isset($_GET['del_img']) && is_numeric($_GET['del_img'])) {
    $imgId = (int)$_GET['del_img'];
    $imgCheck = $pdo->prepare("SELECT li.id FROM listing_images li JOIN listings l ON l.id=li.listing_id WHERE li.id=? AND l.owner_id=?");
    $imgCheck->execute([$imgId, $ownerId]);
    if ($imgCheck->fetch()) {
        deleteListingImage($imgId, $pdo);
        flashMessage('success', 'Image removed.');
    }
    redirect(BASE_URL . '/owner/edit-listing.php?id=' . $listingId);
}

// Save listing edits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $desc  = sanitize($_POST['description'] ?? '');
    $loc   = sanitize($_POST['location'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $type  = sanitize($_POST['type'] ?? 'apartment');

    if (!$title) $errors[] = 'Title is required.';
    if (!$loc)   $errors[] = 'Location is required.';
    if ($price <= 0) $errors[] = 'Valid price is required.';

    if (empty($errors)) {
        // Reset to pending on edit so admin re-approves
        $pdo->prepare("UPDATE listings SET title=?,description=?,location=?,price=?,type=?,status='pending',updated_at=NOW() WHERE id=? AND owner_id=?")
            ->execute([$title, $desc, $loc, $price, $type, $listingId, $ownerId]);

        // Upload new images if any
        if (!empty($_FILES['images']['tmp_name'][0])) {
            uploadImages($_FILES['images'], $listingId, $pdo);
        }

        flashMessage('success', 'Listing updated and resubmitted for approval.');
        redirect(BASE_URL . '/owner/listings.php');
    }
    // Re-load values from POST on error
    $listing['title']       = $_POST['title'];
    $listing['description'] = $_POST['description'];
    $listing['location']    = $_POST['location'];
    $listing['price']       = $_POST['price'];
    $listing['type']        = $_POST['type'];
}

$images = getListingImages($listingId, $pdo);
$pageTitle = 'Edit Listing';
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
      <div><h1>Edit Listing</h1><p>Update your property details</p></div>
      <a href="<?= BASE_URL ?>/owner/listings.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?= renderFlash() ?>
    <?php if ($errors): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><div><?= implode('<br>', $errors) ?></div></div>
    <?php endif; ?>

    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--r-lg);padding:2rem;max-width:800px;">
      <form method="POST" enctype="multipart/form-data">
        <div class="section-label"><i class="fas fa-info-circle"></i> Property Details</div>
        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($listing['title']) ?>" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($listing['description']) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Location *</label>
            <div class="input-icon"><i class="fas fa-map-marker-alt"></i>
              <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($listing['location']) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Monthly Rent (Rs.) *</label>
            <div class="input-icon"><i class="fas fa-tag"></i>
              <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($listing['price']) ?>" required>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Type *</label>
          <select name="type" class="form-control">
            <?php foreach (['apartment','house','room','commercial','land'] as $t): ?>
              <option value="<?= $t ?>" <?= $listing['type']===$t?'selected':'' ?>><?= typeLabel($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (!empty($images)): ?>
        <div class="section-label"><i class="fas fa-images"></i> Current Photos</div>
        <div class="existing-images">
          <?php foreach ($images as $img): ?>
          <div class="existing-item <?= $img['is_primary'] ? 'primary-img' : '' ?>">
            <img src="<?= BASE_URL.'/'.$img['image_path'] ?>" alt="">
            <a href="?id=<?= $listingId ?>&del_img=<?= $img['id'] ?>" class="rm-existing" data-confirm="Remove this image?" title="Delete image"><i class="fas fa-times"></i></a>
          </div>
          <?php endforeach; ?>
        </div>
        <p class="form-hint" style="margin-top:.4rem"><i class="fas fa-star" style="color:var(--accent)"></i> Blue border = primary photo</p>
        <?php endif; ?>

        <div class="section-label"><i class="fas fa-upload"></i> Add More Photos</div>
        <div class="upload-zone" id="uploadZone">
          <input type="file" id="fileInput" name="images[]" multiple accept="image/*">
          <i class="fas fa-cloud-upload-alt"></i>
          <p>Click or drag to add more photos</p>
          <small>JPG, PNG, WEBP supported</small>
        </div>
        <div class="preview-grid" id="previewGrid"></div>

        <div class="alert alert-warning" style="margin-top:1.5rem"><i class="fas fa-exclamation-triangle"></i> Editing will reset this listing to <b>Pending</b> for admin re-approval.</div>
        <div style="display:flex;gap:1rem;margin-top:1rem">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
          <a href="<?= BASE_URL ?>/owner/listings.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
