<?php
// ============================================
// HAMRO DERA - Shared Helper Functions
// ============================================

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data ?? '')));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isOwnerLoggedIn() {
    return isset($_SESSION['owner_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireOwnerLogin() {
    if (!isOwnerLoggedIn()) {
        redirect(BASE_URL . '/owner/login.php');
    }
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

function formatPrice($price) {
    return 'Rs. ' . number_format((float)$price, 0, '.', ',');
}

function getListingPrimaryImage($listing_id, $pdo) {
    $stmt = $pdo->prepare("SELECT image_path FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1");
    $stmt->execute([$listing_id]);
    $row = $stmt->fetch();
    return $row ? $row['image_path'] : null;
}

function getListingImages($listing_id, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute([$listing_id]);
    return $stmt->fetchAll();
}

function uploadImages($files, $listing_id, $pdo) {
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $isFirst = true;

    // Check if listing already has images
    $existing = $pdo->prepare("SELECT COUNT(*) FROM listing_images WHERE listing_id = ?");
    $existing->execute([$listing_id]);
    $hasImages = $existing->fetchColumn() > 0;

    foreach ($files['tmp_name'] as $k => $tmp) {
        if ($files['error'][$k] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($files['name'][$k], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $filename = 'prop_' . $listing_id . '_' . uniqid() . '.' . $ext;
        $dest = UPLOAD_DIR . $filename;
        if (move_uploaded_file($tmp, $dest)) {
            $primary = (!$hasImages && $isFirst) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO listing_images (listing_id, image_path, is_primary) VALUES (?, ?, ?)");
            $stmt->execute([$listing_id, 'assets/images/uploads/' . $filename, $primary]);
            $isFirst = false;
        }
    }
}

function deleteListingImage($image_id, $pdo) {
    $stmt = $pdo->prepare("SELECT image_path FROM listing_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $row = $stmt->fetch();
    if ($row) {
        $fullPath = dirname(__DIR__, 1) . '/' . $row['image_path'];
        if (file_exists($fullPath)) unlink($fullPath);
        $pdo->prepare("DELETE FROM listing_images WHERE id = ?")->execute([$image_id]);
    }
}

function flashMessage($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash() {
    $f = getFlash();
    if (!$f) return '';
    $icons = ['success' => 'fa-check-circle', 'error' => 'fa-times-circle', 'warning' => 'fa-exclamation-triangle', 'info' => 'fa-info-circle'];
    $icon = $icons[$f['type']] ?? 'fa-info-circle';
    return '<div class="alert alert-' . $f['type'] . '"><i class="fas ' . $icon . '"></i> ' . sanitize($f['msg']) . '</div>';
}

function getSecurityQuestionsList() {
    return [
        "What is your mother's maiden name?",
        "What was the name of your first pet?",
        "What city were you born in?",
        "What is your favorite book?",
        "What was the name of your elementary school?",
        "What is your favorite movie?",
        "What was your childhood nickname?",
        "What is the name of your oldest sibling?",
    ];
}

function typeLabel($type) {
    $labels = ['apartment' => 'Apartment', 'house' => 'House', 'room' => 'Room', 'commercial' => 'Commercial', 'land' => 'Land'];
    return $labels[$type] ?? ucfirst($type);
}
