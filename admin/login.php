<?php
// login.php - Secure Admin Login for Zamzy Admin Dashboard
session_start();

if (isset($_SESSION['zamzy_admin_logged']) && $_SESSION['zamzy_admin_logged'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `username` = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['zamzy_admin_logged'] = true;
            $_SESSION['zamzy_admin_id'] = $user['id'];
            $_SESSION['zamzy_admin_user'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zamzy Admin &mdash; Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="../assets/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    :root {
      --bg: #000000;
      --card-bg: #0d121c;
      --primary: #10b981;
      --primary-glow: rgba(16, 185, 129, 0.25);
      --border: rgba(255, 255, 255, 0.09);
      --text: #f1f5f9;
      --muted: #94a3b8;
    }
    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Nunito Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }
    .glow-circle {
      position: absolute;
      width: 450px;
      height: 450px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(16, 185, 129, 0.15), transparent 70%);
      pointer-events: none;
      filter: blur(40px);
    }
    .glow-1 { top: -100px; right: -80px; }
    .glow-2 { bottom: -120px; left: -100px; background: radial-gradient(circle, rgba(245, 158, 11, 0.12), transparent 70%); }

    .login-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 42px 36px;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.05);
      position: relative;
      z-index: 10;
      backdrop-filter: blur(16px);
    }
    .brand-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Poppins', sans-serif;
      font-weight: 800;
      font-size: 1.5rem;
      color: #ffffff;
      text-decoration: none;
      justify-content: center;
      margin-bottom: 24px;
    }
    .brand-mark {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: linear-gradient(135deg, #10b981, #059669);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.2rem;
      box-shadow: 0 6px 16px -2px rgba(16, 185, 129, 0.5);
    }
    .form-control-dark {
      background: #06090f;
      border: 1.5px solid rgba(255, 255, 255, 0.12);
      border-radius: 14px;
      color: #fff;
      padding: 13px 16px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }
    .form-control-dark:focus {
      background: #090e18;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px var(--primary-glow);
      color: #fff;
    }
    .form-control-dark::placeholder {
      color: #64748b;
    }
    .btn-login {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      border-radius: 999px;
      color: #fff;
      font-weight: 700;
      font-family: 'Poppins', sans-serif;
      padding: 13px;
      width: 100%;
      box-shadow: 0 10px 24px -6px rgba(16, 185, 129, 0.5);
      transition: all 0.3s ease;
      font-size: 0.98rem;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 30px -6px rgba(16, 185, 129, 0.65);
      color: #fff;
    }
    .alert-custom {
      background: rgba(239, 68, 68, 0.12);
      border: 1px solid rgba(239, 68, 68, 0.25);
      color: #fca5a5;
      border-radius: 14px;
      font-size: 0.88rem;
      padding: 12px 16px;
    }
    .cred-hint {
      background: rgba(255, 255, 255, 0.04);
      border: 1px dashed rgba(255, 255, 255, 0.12);
      border-radius: 14px;
      padding: 12px 14px;
      font-size: 0.8rem;
      color: var(--muted);
      margin-top: 24px;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="glow-circle glow-1"></div>
  <div class="glow-circle glow-2"></div>

  <div class="login-card">
    <a href="../index.html" class="brand-logo">
      <span class="brand-mark"><i class="bi bi-shield-lock-fill"></i></span>
      <span>Zam<span style="color:var(--primary);">zy</span> Admin</span>
    </a>
    
    <div class="text-center mb-4">
      <h4 class="fw-bold mb-1" style="font-family:'Poppins',sans-serif;">Sign In to Dashboard</h4>
      <p class="small text-muted mb-0">Webinar Contact &amp; Leads Management</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert-custom mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary">Username</label>
        <div class="input-group">
          <input type="text" name="username" class="form-control form-control-dark" placeholder="admin" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label small fw-semibold text-secondary">Password</label>
        <input type="password" name="password" class="form-control form-control-dark" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-login">
        Sign In <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </form>

    <div class="cred-hint">
      <i class="bi bi-key-fill text-warning me-1"></i> Default credentials: <strong>admin</strong> / <strong>admin123</strong>
    </div>
    
    <div class="text-center mt-3">
      <a href="../index.html" class="text-decoration-none small text-muted hover-link">
        <i class="bi bi-arrow-left me-1"></i> Back to Main Website
      </a>
    </div>
  </div>

</body>
</html>
