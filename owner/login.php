<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isOwnerLoggedIn()) redirect(BASE_URL . '/owner/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM owners WHERE email = ?");
    $stmt->execute([$email]);
    $owner = $stmt->fetch();

    if ($owner && password_verify($pass, $owner['password'])) {
        if ($owner['status'] === 'pending') {
            $error = 'Your account is pending admin approval. Please wait for activation.';
        } elseif ($owner['status'] === 'rejected') {
            $error = 'Your account has been rejected. Please contact admin.';
        } else {
            $_SESSION['owner_id']   = $owner['id'];
            $_SESSION['owner_name'] = $owner['name'];
            $_SESSION['owner_email']= $owner['email'];
            flashMessage('success', 'Welcome back, ' . $owner['name'] . '!');
            redirect(BASE_URL . '/owner/index.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Owner Login';
require_once '../includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <span class="auth-icon">🔑</span>
      <h1 class="auth-title">Owner Login</h1>
      <p class="auth-sub">Sign in to manage your property listings</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <div class="input-icon"><i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-icon"><i class="fas fa-lock"></i>
          <input type="password" name="password" class="form-control" placeholder="Your password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem"><i class="fas fa-sign-in-alt"></i> Login</button>
    </form>
    <div class="auth-links">
      <a href="<?= BASE_URL ?>/owner/forgot-password.php">Forgot password?</a> &nbsp;·&nbsp;
      New owner? <a href="<?= BASE_URL ?>/owner/register.php">Register here</a>
    </div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
