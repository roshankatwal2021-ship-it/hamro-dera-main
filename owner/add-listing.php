<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
requireOwnerLogin();

$ownerId = $_SESSION['owner_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title  = sanitize($_POST['title'] ?? '');
    $desc   = sanitize($_POST['description'] ?? '');
    $loc    = sanitize($_POST['location'] ?? '');
    $price  = (float)($_POST['price'] ?? 0);
    $type   = sanitize($_POST['type'] ?? 'apartment');

    if (!$title) $errors[] = 'Title is required.';
    if (!$loc)   $errors[] = 'Location is required.';
    if ($price <= 0) $errors[] = 'Please enter a valid price.';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO listings (owner_id,title,description,location,price,type,status) VALUES (?,?,?,?,?,?,'pending')")->execute([$ownerId,$title,$desc,$loc,$price,$type]);
        $listingId = $pdo->lastInsertId();

        // Handle image uploads
        if (!empty($_FILES['images']['tmp_name'][0])) {
            uploadImages($_FILES['images'], $listingId, $pdo);
        }

        flashMessage('success', 'Listing submitted successfully! It will appear publicly once approved by admin.');
        redirect(BASE_URL . '/owner/listings.php');
    }
}

$pageTitle = 'Add New Listing';
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
      <li><a href="<?= BASE_URL ?>/owner/listings.php"><i class="fas fa-list"></i> My Listings</a></li>
      <li><a href="<?= BASE_URL ?>/owner/add-listing.php" class="active"><i class="fas fa-plus-circle"></i> Add Listing</a></li>
      <li><a href="<?= BASE_URL ?>/"><i class="fas fa-globe"></i> View Site</a></li>
      <li class="logout-link"><a href="<?= BASE_URL ?>/owner/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>
  <main class="dash-main">
    <div class="page-hdr">
      <div><h1>Add New Listing</h1><p>Fill in the details to submit your property</p></div>
      <a href="<?= BASE_URL ?>/owner/listings.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><div><?= implode('<br>', $errors) ?></div></div>
    <?php endif; ?>

    <div style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--r-lg);padding:2rem;max-width:800px;">
      <form method="POST" enctype="multipart/form-data">
        <div class="section-label"><i class="fas fa-info-circle"></i> Property Details</div>
        <div class="form-group">
          <label>Property Title *</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Modern 2BHK Apartment in Thamel" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="5" placeholder="Describe your property – amenities, nearby places, special features…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Location *</label>
            <div class="input-icon"><i class="fas fa-map-marker-alt"></i>
              <input type="text" name="location" class="form-control" placeholder="e.g. Thamel, Kathmandu" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Monthly Rent (Rs.) *</label>
            <div class="input-icon"><i class="fas fa-tag"></i>
              <input type="number" name="price" class="form-control" placeholder="e.g. 15000" min="1" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label>Property Type *</label>
          <select name="type" class="form-control">
            <?php foreach (['apartment','house','room','hostels'] as $t): ?>
              <option value="<?= $t ?>" <?= (($_POST['type'] ?? 'apartment') === $t) ? 'selected' : '' ?>><?= typeLabel($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="section-label"><i class="fas fa-images"></i> Property Photos</div>
        <div class="upload-zone" id="uploadZone">
          <input type="file" id="fileInput" name="images[]" multiple accept="image/*">
          <i class="fas fa-cloud-upload-alt"></i>
          <p>Click to upload or drag & drop photos here</p>
          <small>JPG, PNG, WEBP — Multiple files supported (First image will be the main photo)</small>
        </div>
        <div class="preview-grid" id="previewGrid"></div>

        <div class="alert alert-info" style="margin-top:1.5rem"><i class="fas fa-info-circle"></i> Your listing will be submitted for admin review and published once approved.</div>
        <div style="display:flex;gap:1rem;margin-top:1rem">
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Listing</button>
          <a href="<?= BASE_URL ?>/owner/listings.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>
<?php require_once '../includes/footer.php'; ?>
