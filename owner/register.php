<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isOwnerLoggedIn()) redirect(BASE_URL . '/owner/index.php');

$errors = [];
$questions = getSecurityQuestionsList();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $sq1   = sanitize($_POST['sq1'] ?? ''); $sa1 = sanitize($_POST['sa1'] ?? '');
    $sq2   = sanitize($_POST['sq2'] ?? ''); $sa2 = sanitize($_POST['sa2'] ?? '');
    $sq3   = sanitize($_POST['sq3'] ?? ''); $sa3 = sanitize($_POST['sa3'] ?? '');

    if (!$name)  $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!preg_match('/^\d{10}$/', preg_replace('/\D/', '', $phone))) $errors[] = 'Phone number must be exactly 10 digits.';
    if (!preg_match('/^(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{8,10}$/', $pass)) $errors[] = 'Password must be 8-10 characters long, including at least one number and one special character.';
    if ($pass !== $pass2)  $errors[] = 'Passwords do not match.';
    if (!$sq1 || !$sa1 || !$sq2 || !$sa2 || !$sq3 || !$sa3) $errors[] = 'Please answer all 3 security questions.';

    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM owners WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO owners (name,email,phone,password,status,sec_q1,sec_a1,sec_q2,sec_a2,sec_q3,sec_a3) VALUES (?,?,?,?,'pending',?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $sq1, strtolower(trim($sa1)), $sq2, strtolower(trim($sa2)), $sq3, strtolower(trim($sa3))]);
            flashMessage('success', 'Registration successful! Your account is pending admin approval. You will be able to login once approved.');
            redirect(BASE_URL . '/owner/login.php');
        }
    }
}

$pageTitle = 'Owner Registration';
require_once '../includes/header.php';
?>
<div class="auth-page" style="padding:2rem 1rem;align-items:flex-start;">
  <div style="width:100%;max-width:560px;margin:0 auto;">
    <div class="auth-card">
      <div class="auth-header">
        <center>
  <img src="../assets/images/logo.png" alt="Hamro Dera Logo">
</center>
        <h1 class="auth-title">Owner Registration</h1>
        <p class="auth-sub">Create an owner account to manage your listings</p>
      </div>

      <?php if ($errors): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><div><?= implode('<br>', $errors) ?></div></div>
      <?php endif; ?>

      <form method="POST">
        <div class="section-label"><i class="fas fa-user"></i> Personal Info</div>
        <div class="form-group">
          <label>Full Name *</label>
          <div class="input-icon"><i class="fas fa-user"></i>
            <input type="text" name="name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Email *</label>
            <div class="input-icon"><i class="fas fa-envelope"></i>
              <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label>Phone *</label>
            <div class="input-icon"><i class="fas fa-phone"></i>
              <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX (10 digits)" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            </div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password *</label>
            <div class="input-icon"><i class="fas fa-lock"></i>
              <input type="password" name="password" class="form-control" placeholder="8-10 chars, 1 num, 1 spec" required>
            </div>
          </div>
          <div class="form-group">
            <label>Confirm Password *</label>
            <div class="input-icon"><i class="fas fa-lock"></i>
              <input type="password" name="password2" class="form-control" placeholder="Repeat password" required>
            </div>
          </div>
        </div>

        <div class="section-label"><i class="fas fa-shield-alt"></i> Security Questions (for password recovery)</div>
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="form-group">
          <label>Question <?= $i ?> *</label>
          <select name="sq<?= $i ?>" class="form-control" required>
            <option value="">— Select a question —</option>
            <?php foreach ($questions as $q): ?>
              <option value="<?= htmlspecialchars($q) ?>" <?= (($_POST["sq$i"] ?? '') === $q) ? 'selected' : '' ?>><?= htmlspecialchars($q) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Answer <?= $i ?> *</label>
          <input type="text" name="sa<?= $i ?>" class="form-control" placeholder="Your answer (case-insensitive)" value="<?= htmlspecialchars($_POST["sa$i"] ?? '') ?>" required>
        </div>
        <?php endfor; ?>

        <div class="alert alert-info" style="margin-top:1rem"><i class="fas fa-info-circle"></i> Your account will be reviewed by admin before you can log in.</div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem"><i class="fas fa-user-plus"></i> Create Account</button>
      </form>
      <div class="auth-links">Already have an account? <a href="<?= BASE_URL ?>/owner/login.php">Login here</a></div>
    </div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
