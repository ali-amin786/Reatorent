<?php
// admin/login.php - Clean Staff Login with Rate Limiting & Security
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect directly to dashboard
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$max_attempts = 5;
$lockout_seconds = 300; // 5 minutes

// Check lockout state
if (isset($_SESSION['login_lockout_until']) && time() < $_SESSION['login_lockout_until']) {
    $error = "Too many attempts. Try again in 5 minutes.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Incorrect username or password.";
    } else {
        $username = clean($conn, $_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Incorrect username or password.";
        } else {
            $sql = "SELECT * FROM admins WHERE username = '$username' LIMIT 1";
            $result = mysqli_query($conn, $sql);
            $admin = mysqli_fetch_assoc($result);

            if ($admin && password_verify($password, $admin['password_hash'])) {
                // Successful Login
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['last_activity'] = time();

                // Reset failed attempt counters
                unset($_SESSION['login_attempts']);
                unset($_SESSION['login_lockout_until']);

                header("Location: dashboard.php");
                exit;
            } else {
                // Failed attempt - generic message only
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= $max_attempts) {
                    $_SESSION['login_lockout_until'] = time() + $lockout_seconds;
                    $error = "Too many attempts. Try again in 5 minutes.";
                } else {
                    $error = "Incorrect username or password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            background-color: var(--admin-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-top: 4px solid var(--admin-primary);
            border-radius: var(--radius-md);
            padding: 36px 30px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        }
        .password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--admin-text-muted);
            font-size: 0.9rem;
            padding: 4px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Restaurant Logo Mark -->
    <div style="text-align: center; margin-bottom: 24px;">
        <span style="font-size: 2.4rem; display: inline-block; line-height: 1;">🔥</span>
        <h2 style="font-size: 1.25rem; font-weight: 600; color: var(--admin-text-dark); margin-top: 6px;">
            A1 Peshawari Kabab
        </h2>
        <p style="font-size: 0.8rem; color: var(--admin-text-muted);">Staff Portal</p>
    </div>

    <?php if (isset($_GET['timeout'])): ?>
        <div style="background: var(--admin-warning-light); border: 1px solid rgba(224, 161, 6, 0.4); color: #9A6B00; font-size: 0.85rem; padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 18px;">
            Session expired due to 30 mins of inactivity. Please log in again.
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: var(--admin-primary-light); border: 1px solid rgba(227, 28, 35, 0.3); color: var(--admin-primary); font-size: 0.85rem; padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 18px;">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <?php echo csrf_field(); ?>

        <div class="admin-form-group">
            <label for="username" class="admin-form-label">Username</label>
            <input type="text" id="username" name="username" class="admin-form-control" required autofocus placeholder="Enter your username" value="<?php echo e($_POST['username'] ?? ''); ?>">
        </div>

        <div class="admin-form-group">
            <label for="password" class="admin-form-label">Password</label>
            <div class="password-wrap">
                <input type="password" id="password" name="password" class="admin-form-control" required placeholder="Enter your password" style="padding-right: 40px;">
                <button type="button" class="password-toggle-btn" id="togglePasswordBtn" aria-label="Toggle password visibility">👁️</button>
            </div>
        </div>

        <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%; padding: 11px; font-size: 0.95rem; margin-top: 8px;">
            Login
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px;">
        <a href="../index.php" style="color: var(--admin-text-muted); font-size: 0.82rem;">&larr; Back to Website</a>
    </div>
</div>

<script>
const toggleBtn = document.getElementById('togglePasswordBtn');
const passInput = document.getElementById('password');
if (toggleBtn && passInput) {
    toggleBtn.addEventListener('click', function () {
        const isPassword = passInput.type === 'password';
        passInput.type = isPassword ? 'text' : 'password';
        toggleBtn.textContent = isPassword ? '🙈' : '👁️';
    });
}
</script>
</body>
</html>
