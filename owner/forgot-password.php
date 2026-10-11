<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isOwnerLoggedIn()) redirect(BASE_URL . '/owner/index.php');

// Multi-step process tracked via session
// Step 1: Enter email → Step 2: Answer 2 security Qs → Step 3: Reset password

$step = $_SESSION['fp_step'] ?? 1;
$error = '';

// --- STEP 1: Email submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    $email = sanitize($_POST['email'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM owners WHERE email = ?");
    $stmt->execute([$email]);
    $owner = $stmt->fetch();
    if ($owner) {
        // Pick 2 random questions from the 3 stored
        $allQ = [
            ['q' => $owner['sec_q1'], 'a' => $owner['sec_a1'], 'key' => '1'],
            ['q' => $owner['sec_q2'], 'a' => $owner['sec_a2'], 'key' => '2'],
            ['q' => $owner['sec_q3'], 'a' => $owner['sec_a3'], 'key' => '3'],
        ];
        shuffle($allQ);
        $chosen = array_slice($allQ, 0, 2);
        $_SESSION['fp_owner_id'] = $owner['id'];
        $_SESSION['fp_chosen']   = $chosen;
        $_SESSION['fp_step']     = 2;
        redirect(BASE_URL . '/owner/forgot-password.php');
    } else {
        $error = 'No account found with this email address.';
    }
}

// --- STEP 2: Answer security questions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2'])) {
    $chosen = $_SESSION['fp_chosen'] ?? [];
    $a1 = strtolower(trim($_POST['ans1'] ?? ''));
    $a2 = strtolower(trim($_POST['ans2'] ?? ''));
    $ok1 = ($a1 === strtolower(trim($chosen[0]['a'])));
    $ok2 = ($a2 === strtolower(trim($chosen[1]['a'])));
    if ($ok1 && $ok2) {
        $_SESSION['fp_step'] = 3;
        redirect(BASE_URL . '/owner/forgot-password.php');
    } else {
        $error = 'One or more answers are incorrect. Please try again.';
        $_SESSION['fp_step'] = 2;
    }
}

// --- STEP 3: Reset password ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3'])) {
    $newPass  = $_POST['new_password'] ?? '';
    $newPass2 = $_POST['new_password2'] ?? '';
    if (strlen($newPass) < 6) {
        $error = 'Password must be at least 6 characters.';
        $_SESSION['fp_step'] = 3;
    } elseif ($newPass !== $newPass2) {
        $error = 'Passwords do not match.';
        $_SESSION['fp_step'] = 3;
    } else {
        $ownerId = $_SESSION['fp_owner_id'];
        $pdo->prepare("UPDATE owners SET password = ? WHERE id = ?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $ownerId]);
        unset($_SESSION['fp_step'], $_SESSION['fp_owner_id'], $_SESSION['fp_chosen']);
        flashMessage('success', 'Password reset successfully! You can now login with your new password.');
        redirect(BASE_URL . '/owner/login.php');
    }
}

$step   = $_SESSION['fp_step'] ?? 1;
$chosen = $_SESSION['fp_chosen'] ?? [];

$pageTitle = 'Forgot Password';
require_once '../includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <span class="auth-icon">🔒</span>
      <h1 class="auth-title">Reset Password</h1>
      <p class="auth-sub">Verify your identity to reset your password</p>
    </div>

    <!-- Steps indicator -->
    <div class="steps" style="margin-bottom:1.5rem;">
      <div class="step <?= $step>=1?'active':'' ?> <?= $step>1?'done':'' ?>">
        <div class="step-dot"><?= $step>1?'✓':'1' ?></div>
      </div>
      <div class="step-line <?= $step>1?'done':'' ?>"></div>
      <div class="step <?= $step>=2?'active':'' ?> <?= $step>2?'done':'' ?>">
        <div class="step-dot"><?= $step>2?'✓':'2' ?></div>
      </div>
      <div class="step-line <?= $step>2?'done':'' ?>"></div>
      <div class="step <?= $step>=3?'active':'' ?>">
        <div class="step-dot">3</div>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
    <form method="POST">
      <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.2rem;">Enter your registered email to begin the recovery process.</p>
      <div class="form-group">
        <label>Email Address</label>
        <div class="input-icon"><i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control" placeholder="your@email.com" required autofocus>
        </div>
      </div>
      <button type="submit" name="step1" class="btn btn-primary btn-full"><i class="fas fa-arrow-right"></i> Continue</button>
    </form>

    <?php elseif ($step === 2): ?>
    <form method="POST">
      <p style="color:var(--muted);font-size:.88rem;margin-bottom:1.2rem;">Please answer both security questions correctly.</p>
      <?php foreach ($chosen as $i => $q): ?>
      <div class="form-group">
        <label><?= htmlspecialchars($q['q']) ?></label>
        <input type="text" name="ans<?= $i+1 ?>" class="form-control" placeholder="Your answer" required>
      </div>
      <?php endforeach; ?>
      <button type="submit" name="step2" class="btn btn-primary btn-full"><i class="fas fa-check"></i> Verify Answers</button>
    </form>

    <?php elseif ($step === 3): ?>
    <form method="POST">
      <div class="alert alert-success"><i class="fas fa-check-circle"></i>Identity verified! Set your new password.</div>
      <div class="form-group">
        <label>New Password</label>
        <div class="input-icon"><i class="fas fa-lock"></i>
          <input type="password" name="new_password" class="form-control" placeholder="Min. 6 characters" required autofocus>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <div class="input-icon"><i class="fas fa-lock"></i>
          <input type="password" name="new_password2" class="form-control" placeholder="Repeat new password" required>
        </div>
      </div>
      <button type="submit" name="step3" class="btn btn-primary btn-full"><i class="fas fa-save"></i> Reset Password</button>
    </form>
    <?php endif; ?>

    <div class="auth-links">Remembered it? <a href="<?= BASE_URL ?>/owner/login.php">Back to Login</a></div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
