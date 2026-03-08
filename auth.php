<?php
// ═══════════════════════════════════════════════
//  BikeValue — Authentication Handler
// ═══════════════════════════════════════════════
session_start();
require_once 'config.php';

$action = $_POST['action'] ?? '';

switch ($action) {

    // ── SIGN UP ──
    case 'signup':
        $user_id  = trim($_POST['user_id']  ?? '');
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        if (strlen($user_id) < 3) {
            $_SESSION['auth_error'] = 'Username must be at least 3 characters.';
            $_SESSION['open_modal'] = 'signupModal';
            header('Location: index.php'); exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['auth_error'] = 'Please enter a valid email address.';
            $_SESSION['open_modal'] = 'signupModal';
            header('Location: index.php'); exit;
        }
        if (strlen($password) < 6) {
            $_SESSION['auth_error'] = 'Password must be at least 6 characters.';
            $_SESSION['open_modal'] = 'signupModal';
            header('Location: index.php'); exit;
        }

        // Check duplicate
        $stmt = $pdo->prepare('SELECT id FROM users WHERE user_id = ? OR email = ?');
        $stmt->execute([$user_id, $email]);
        if ($stmt->fetch()) {
            $_SESSION['auth_error'] = 'That username or email is already registered. Please log in.';
            $_SESSION['open_modal'] = 'signupModal';
            header('Location: index.php'); exit;
        }

        // Insert
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (user_id, email, password, role) VALUES (?, ?, ?, "user")');
        $stmt->execute([$user_id, $email, $hash]);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['email']   = $email;
        $_SESSION['role']    = 'user';
        header('Location: predict.php'); exit;

    // ── USER LOGIN ──
    case 'user_login':
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = "user"');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = 'user';
            header('Location: predict.php'); exit;
        }

        $_SESSION['auth_error'] = 'Invalid email or password.';
        $_SESSION['open_modal'] = 'loginModal';
        header('Location: index.php'); exit;

    // ── ADMIN LOGIN ──
    case 'admin_login':
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = "admin"');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['user_id'];
            $_SESSION['email']   = $admin['email'];
            $_SESSION['role']    = 'admin';
            header('Location: admin.php'); exit;
        }

        $_SESSION['auth_error'] = 'Invalid admin credentials.';
        $_SESSION['open_modal'] = 'adminModal';
        header('Location: index.php'); exit;

    // ── LOGOUT ──
    case 'logout':
        session_destroy();
        header('Location: index.php'); exit;

    default:
        header('Location: index.php'); exit;
}
