<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isAdminLoggedIn()) redirect(BASE_URL . '/admin/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['password'])) {
        $_SESSION['admin_id']    = $admin['id'];
        $_SESSION['admin_name']  = $admin['name'];
        $_SESSION['admin_email'] = $admin['email'];
        flashMessage('success', 'Welcome, ' . $admin['name'] . '!');
        redirect(BASE_URL . '/admin/index.php');
    } else {
        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Admin Login';
require_once '../includes/header.php';
?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <span class="auth-icon">🛡️</span>
      <h1 class="auth-title">Admin Login</h1>
      <p class="auth-sub">Restricted access — Authorized personnel only</p>
    </div>
    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Admin Email</label>
        <div class="input-icon"><i class="fas fa-envelope"></i>
          <input type="email" name="email" class="form-control" placeholder="admin@hamrodera.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="form-group">
        <label>Password</label>
        <div class="input-icon"><i class="fas fa-lock"></i>
          <input type="password" name="password" class="form-control" placeholder="Admin password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem"><i class="fas fa-shield-alt"></i> Admin Login</button>
    </form>
    <div class="auth-links"><a href="<?= BASE_URL ?>/">← Back to Homepage</a></div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
