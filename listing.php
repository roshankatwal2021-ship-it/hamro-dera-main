<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/'); }

// Fetch listing (approved only)
$stmt = $pdo->prepare(
    "SELECT l.*, o.name AS owner_name, o.phone AS owner_phone
     FROM listings l
     JOIN owners o ON l.owner_id = o.id
     WHERE l.id = ? AND l.status = 'approved'"
);
$stmt->execute([$id]);
$listing = $stmt->fetch();
if (!$listing) {
    header('HTTP/1.0 404 Not Found');
    require_once 'includes/header.php';
    echo '<div class="container" style="padding:5rem 1rem;text-align:center;">
        <h2 style="color:var(--text2)">Listing not found</h2>
        <p style="color:var(--muted);margin:1rem 0">It may have been removed or is not yet approved.</p>
        <a href="' . BASE_URL . '/" class="btn btn-primary" style="display:inline-flex;margin-top:1rem">← Back to Browse</a>
    </div>';
    require_once 'includes/footer.php';
    exit;
}

$images = getListingImages($id, $pdo);

$phone    = $listing['owner_phone'];
$waNumber = '977' . ltrim(preg_replace('/\D/', '', $phone), '0');
$waMsg    = urlencode("Hi, I'm interested in your property: " . $listing['title'] . " listed on Hamro Dera.");

// --- Handle Review Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rName = sanitize($_POST['reviewer_name'] ?? '');
    $rRating = (int)($_POST['rating'] ?? 5);
    $rComment = sanitize($_POST['comment'] ?? '');
    if ($rName && $rComment && $rRating >= 1 && $rRating <= 5) {
        $rStmt = $pdo->prepare("INSERT INTO reviews (listing_id, user_name, rating, comment) VALUES (?, ?, ?, ?)");
        $rStmt->execute([$id, $rName, $rRating, $rComment]);
        flashMessage('success', 'Your review has been added!');
        redirect(BASE_URL . '/listing.php?id=' . $id);
    } else {
        flashMessage('error', 'Please provide a valid name, rating, and comment.');
        redirect(BASE_URL . '/listing.php?id=' . $id);
    }
}

// --- Fetch Reviews ---
$revStmt = $pdo->prepare("SELECT * FROM reviews WHERE listing_id = ? ORDER BY created_at DESC");
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll();

// --- Calculate Average Rating ---
$avgRating = 0;
if (count($reviews) > 0) {
    $sum = 0;
    foreach ($reviews as $rev) $sum += (int)$rev['rating'];
    $avgRating = round($sum / count($reviews), 1);
}

$pageTitle = htmlspecialchars($listing['title']);
$pageDesc  = htmlspecialchars(mb_substr($listing['description'] ?? '', 0, 155));
require_once 'includes/header.php';
?>

<div class="listing-detail">
  <!-- LEFT: Gallery + Details -->
  <div>
    <a href="<?= BASE_URL ?>/" class="back-link"><i class="fas fa-arrow-left"></i> Back to Browse</a>

    <!-- Gallery -->
    <div class="gallery">
      <div class="gallery-main">
        <?php if (!empty($images)): ?>
          <img id="galleryMain" src="<?= BASE_URL . '/' . htmlspecialchars($images[0]['image_path']) ?>" alt="<?= htmlspecialchars($listing['title']) ?>">
          <?php if (count($images) > 1): ?>
          <div class="gallery-nav">
            <button id="galleryPrev" title="Previous"><i class="fas fa-chevron-left"></i></button>
            <button id="galleryNext" title="Next"><i class="fas fa-chevron-right"></i></button>
          </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="no-img-big"><i class="fas fa-home"></i></div>
        <?php endif; ?>
      </div>
      <?php if (count($images) > 1): ?>
      <div class="gallery-thumbs">
        <?php foreach ($images as $img): ?>
          <div class="gallery-thumb">
            <img src="<?= BASE_URL . '/' . htmlspecialchars($img['image_path']) ?>" alt="thumbnail">
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Description -->
    <div class="info-panel" style="margin-top:1.5rem;">
      <h3 style="font-size:1rem;font-weight:700;margin-bottom:0.8rem;color:var(--text2);text-transform:uppercase;letter-spacing:.05em;font-size:.8rem;">About this property</h3>
      <p class="desc-text"><?= nl2br(htmlspecialchars($listing['description'] ?? 'No description provided.')) ?></p>
    </div>
  </div>

  <!-- RIGHT: Info + Contact -->
  <div class="listing-info">
    <!-- Main Info -->
    <div class="info-panel">
      <span class="card-type-badge" style="position:static;display:inline-block;margin-bottom:.8rem;"><?= typeLabel($listing['type']) ?></span>
      <h1 class="listing-title-big" style="margin-bottom:0.4rem;"><?= htmlspecialchars($listing['title']) ?></h1>
      <?php if($avgRating > 0): ?>
      <div style="margin-bottom:0.8rem;color:var(--warning);font-size:0.9rem">
        <?= str_repeat('<i class="fas fa-star"></i>', round($avgRating)) ?><?= str_repeat('<i class="far fa-star"></i>', 5 - round($avgRating)) ?>
        <span style="color:var(--text2);margin-left:6px;font-size:0.85rem">(<?= $avgRating ?> / 5 from <?= count($reviews) ?> reviews)</span>
      </div>
      <?php endif; ?>
      <div class="listing-meta-row">
        <div class="meta-chip"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($listing['location']) ?></div>
        <div class="meta-chip"><i class="fas fa-calendar-alt"></i><?= date('M j, Y', strtotime($listing['created_at'])) ?></div>
      </div>
      <div class="divider"></div>
      <div class="listing-price-big">
        <?= formatPrice($listing['price']) ?>
        <small>per month</small>
      </div>
    </div>

    <!-- Contact Card -->
    <div class="contact-panel">
      <h3><i class="fas fa-user-circle" style="color:var(--accent);margin-right:6px"></i>Contact Owner</h3>
      <div class="owner-row">
        <div class="owner-av"><?= strtoupper(mb_substr($listing['owner_name'], 0, 1)) ?></div>
        <div>
          <div class="owner-nm"><a href="<?= BASE_URL ?>/owner-profile.php?id=<?= $listing['owner_id'] ?>" style="color:var(--accent);text-decoration:none;"><?= htmlspecialchars($listing['owner_name']) ?> <i class="fas fa-external-link-alt" style="font-size:0.8em"></i></a></div>
          <div class="owner-ph"><i class="fas fa-phone" style="margin-right:4px;font-size:.75rem"></i><?= htmlspecialchars($phone) ?></div>
        </div>
      </div>
      <div class="contact-btns">
        <a href="tel:<?= htmlspecialchars(preg_replace('/\D/','',$phone)) ?>" class="btn-call">
          <i class="fas fa-phone-alt"></i> Call Owner
        </a>
        <a href="https://wa.me/<?= $waNumber ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener" class="btn-whatsapp">
          <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
      </div>
    </div>

    <!-- Property Details -->
    <div class="info-panel">
      <h3 style="font-size:.82rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);margin-bottom:1rem;">Property Details</h3>
      <table style="width:100%;font-size:.88rem;border-collapse:collapse;">
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:.5rem 0;color:var(--muted)">Type</td>
          <td style="padding:.5rem 0;font-weight:600;text-align:right"><?= typeLabel($listing['type']) ?></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:.5rem 0;color:var(--muted)">Location</td>
          <td style="padding:.5rem 0;font-weight:600;text-align:right"><?= htmlspecialchars($listing['location']) ?></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border)">
          <td style="padding:.5rem 0;color:var(--muted)">Monthly Rent</td>
          <td style="padding:.5rem 0;font-weight:600;color:var(--accent);text-align:right"><?= formatPrice($listing['price']) ?></td>
        </tr>
        <tr>
          <td style="padding:.5rem 0;color:var(--muted)">Listed</td>
          <td style="padding:.5rem 0;font-weight:600;text-align:right"><?= date('M j, Y', strtotime($listing['created_at'])) ?></td>
        </tr>
      </table>
    </div>

    <!-- Reviews Section -->
    <div class="info-panel" id="reviews">
      <h3 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;color:var(--text)">Reviews (<?= count($reviews) ?>)</h3>
      
      <?php if(empty($reviews)): ?>
        <p style="color:var(--muted);font-size:0.9rem">No reviews yet. Be the first to comment!</p>
      <?php else: ?>
        <div class="reviews-list" style="display:flex;flex-direction:column;gap:1.2rem;margin-bottom:1.5rem">
          <?php foreach($reviews as $r): ?>
            <div class="review-item" style="border-bottom:1px solid var(--border);padding-bottom:1.2rem;">
              <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <strong style="color:var(--text);font-size:0.95rem"><?= htmlspecialchars($r['user_name']) ?></strong>
                <span style="color:var(--warning);font-size:0.85rem">
                  <?= str_repeat('<i class="fas fa-star"></i>', $r['rating']) ?><?= str_repeat('<i class="far fa-star"></i>', 5 - $r['rating']) ?>
                </span>
              </div>
              <p style="color:var(--text2);font-size:0.9rem;margin-bottom:6px;line-height:1.5"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
              <small style="color:var(--muted);font-size:0.75rem"><?= date('M j, Y', strtotime($r['created_at'])) ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h4 style="font-size:1rem;margin-bottom:1rem;margin-top:2rem;color:var(--text)">Leave a Review</h4>
      <form method="POST" action="#reviews">
        <input type="hidden" name="submit_review" value="1">
        <div style="margin-bottom:1rem">
          <label style="display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px">Your Name *</label>
          <input type="text" name="reviewer_name" class="form-control" required style="width:100%;padding:0.7rem;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px">
        </div>
        <div style="margin-bottom:1rem">
          <label style="display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px">Rating *</label>
          <select name="rating" class="form-control" required style="width:100%;padding:0.7rem;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px">
            <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
            <option value="4">⭐⭐⭐⭐ (4/5)</option>
            <option value="3">⭐⭐⭐ (3/5)</option>
            <option value="2">⭐⭐ (2/5)</option>
            <option value="1">⭐ (1/5)</option>
          </select>
        </div>
        <div style="margin-bottom:1rem">
          <label style="display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px">Comment *</label>
          <textarea name="comment" rows="3" class="form-control" required style="width:100%;padding:0.7rem;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="padding:0.6rem 1.2rem;width:100%"><i class="fas fa-paper-plane"></i> Submit Review</button>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
