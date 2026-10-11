<?php
// header.php — included by every public page (already has session + config loaded by the calling page)
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' – Hamro Dera' : 'Hamro Dera – Find Your Perfect Rental' ?></title>
  <meta name="description" content="<?= isset($pageDesc) ? sanitize($pageDesc) : "Hamro Dera – Nepal's trusted rental property platform. Find apartments, houses, and rooms for rent." ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" href="assets/images/logo.png" type="image/png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
  <div class="nav-container">
    <a href="<?= BASE_URL ?>/" class="nav-logo">
      <span class="logo-text">Hamro <span class="accent">Dera</span></span>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>
    <ul class="nav-links" id="navLinks">
      <li><a href="<?= BASE_URL ?>/browse.php" <?= basename($_SERVER['PHP_SELF']) === 'browse.php' ? 'class="active"' : '' ?>>Browse</a></li>
      <?php if (isOwnerLoggedIn()): ?>
        <li><a href="<?= BASE_URL ?>/owner/index.php">Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/owner/add-listing.php">+ List Property</a></li>
        <li><a href="<?= BASE_URL ?>/owner/logout.php" class="btn-nav" style="background:rgba(239,68,68,0.15)!important;color:#f87171!important;box-shadow:none!important;">Logout</a></li>
      <?php else: ?>
        <li><a href="<?= BASE_URL ?>/owner/login.php">Owner Login</a></li>
        <li><a href="<?= BASE_URL ?>/owner/register.php" class="btn-nav">Register</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<?php if ($flash): ?>
<div class="global-flash">
  <div class="alert alert-<?= $flash['type'] ?>">
    <i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : ($flash['type'] === 'error' ? 'fa-times-circle' : 'fa-info-circle') ?>"></i>
    <?= sanitize($flash['msg']) ?>
  </div>
</div>
<?php endif; ?>
