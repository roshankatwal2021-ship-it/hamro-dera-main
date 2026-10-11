<?php
require_once 'config/db.php';

$errors   = [];
$messages = [];

// Create uploads directory
$uploadDir = __DIR__ . '/assets/images/uploads/';
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        $messages[] = '✅ Uploads directory created at: assets/images/uploads/';
    } else {
        $errors[] = '❌ Could not create uploads directory. Create it manually: assets/images/uploads/';
    }
} else {
    $messages[] = '✅ Uploads directory already exists.';
}

// Seed admin account
$adminEmail    = 'admin@hamrodera.com';
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$adminName     = 'Super Admin';

$secQ1 = "What is your pet name?";
$secA1 = password_hash('dog', PASSWORD_DEFAULT);
$secQ2 = "What is your birth city?";
$secA2 = password_hash('kathmandu', PASSWORD_DEFAULT);
$secQ3 = "What is your school name?";
$secA3 = password_hash('school', PASSWORD_DEFAULT);

$check = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
$check->execute([$adminEmail]);

if ($check->fetch()) {
    $messages[] = '✅ Admin account already exists. No changes made.';
} else {
    $stmt = $pdo->prepare("INSERT INTO admin (name, email, password, sec_q1, sec_a1, sec_q2, sec_a2, sec_q3, sec_a3)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$adminName, $adminEmail, $adminPassword, $secQ1, $secA1, $secQ2, $secA2, $secQ3, $secA3]);
    $messages[] = '✅ Admin account created successfully!';
}

// Seed sample approved owner and listings for demo
$ownerEmail = 'demo@hamrodera.com';
$checkOwner = $pdo->prepare("SELECT id FROM owners WHERE email = ?");
$checkOwner->execute([$ownerEmail]);

if (!$checkOwner->fetch()) {
    $ownerPass = password_hash('demo123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO owners (name, email, phone, password, status, sec_q1, sec_a1, sec_q2, sec_a2, sec_q3, sec_a3)
                           VALUES (?, ?, ?, ?, 'approved', ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Demo Owner', $ownerEmail, '9841000000', $ownerPass,
        'What is your pet name?', password_hash('cat', PASSWORD_DEFAULT),
        'What is your birth city?', password_hash('pokhara', PASSWORD_DEFAULT),
        'What is your school name?', password_hash('demo', PASSWORD_DEFAULT),
    ]);
    $ownerId = $pdo->lastInsertId();

    // Sample listings
    $listings = [
        ['Modern Apartment in Thamel', 'A beautiful 2BHK apartment fully furnished with all amenities. Close to restaurants and tourist spots. Perfect for professionals and families.', 'Thamel, Kathmandu', 25000, 'apartment'],
        ['Cozy Room near Patan', 'Single room available near Patan Durbar Square. Attached bathroom, 24hr water supply, WiFi included.', 'Patan, Lalitpur', 8000, 'room'],
        ['Spacious House in Bhaktapur', '3BHK independent house with garden. 2 parking spaces. Quiet neighborhood, great for families.', 'Bhaktapur', 35000, 'house'],
        ['Commercial Space - Newroad', 'Prime commercial space on the ground floor of a busy market. Suitable for shops, offices.', 'Newroad, Kathmandu', 60000, 'commercial'],
        ['Land for Rent - Baneshwor', 'Open land available for temporary commercial use or event space. 5 ropanis.', 'Baneshwor, Kathmandu', 15000, 'land'],
        ['Studio Apartment - Naxal', 'Brand new studio apartment with modern interiors, AC, geyser, and parking.', 'Naxal, Kathmandu', 18000, 'apartment'],
    ];

    $lstmt = $pdo->prepare("INSERT INTO listings (owner_id, title, description, location, price, type, status) VALUES (?, ?, ?, ?, ?, ?, 'approved')");
    foreach ($listings as $l) {
        $lstmt->execute([$ownerId, $l[0], $l[1], $l[2], $l[3], $l[4]]);
    }

    $messages[] = '✅ Demo owner (demo@hamrodera.com / demo123) and 6 sample listings created.';
} else {
    $messages[] = '✅ Demo data already exists.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Hamro Dera Setup</title>
<style>
  body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .box{background:#1e293b;border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:2.5rem;max-width:560px;width:90%}
  h1{font-size:1.6rem;margin-bottom:0.5rem;color:#38bdf8}
  p{color:#94a3b8;margin-bottom:1.5rem}
  .msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:.6rem;font-size:.9rem}
  .ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#86efac}
  .err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
  .creds{background:rgba(56,189,248,.1);border:1px solid rgba(56,189,248,.3);border-radius:10px;padding:1.2rem;margin-top:1.5rem}
  .creds h3{color:#38bdf8;margin-bottom:.7rem}
  .creds code{font-family:monospace;font-size:.95rem}
  .creds table td{padding:.3rem .8rem}
  a.btn{display:inline-block;margin-top:1.5rem;background:linear-gradient(135deg,#38bdf8,#0284c7);color:#fff;padding:.7rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:700}
</style>
</head>
<body>
<div class="box">
  <h1>🏠 Hamro Dera – Setup</h1>
  <p>One-time setup to create the admin account and sample data.</p>
  <?php foreach ($messages as $m): ?>
    <div class="msg ok"><?= $m ?></div>
  <?php endforeach; ?>
  <?php foreach ($errors as $e): ?>
    <div class="msg err"><?= $e ?></div>
  <?php endforeach; ?>
  <div class="creds">
    <h3>🔑 Login Credentials</h3>
    <table>
      <tr><td><b>Admin</b></td><td><code>admin@hamrodera.com</code> / <code>admin123</code></td></tr>
      <tr><td><b>Demo Owner</b></td><td><code>demo@hamrodera.com</code> / <code>demo123</code></td></tr>
    </table>
  </div>
  <a class="btn" href="<?= BASE_URL ?>/">🏠 Go to Homepage</a>
</div>
</body>
</html>
