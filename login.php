<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Redirect to the correct homepage.
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . role_home($_SESSION['role']));
    exit;
}

function role_home(string $role): string
{
    return match ($role) {
        'student'   => '/student/index.php',
        'professor' => '/professor/index.php',
        'secretary' => '/secretary/index.php',
        default     => '/login.php',
    };
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Παρακαλώ συμπληρώστε όνομα χρήστη και κωδικό.';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash, role, full_name FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: ' . role_home($user['role']));
            exit;
        } else {
            $error = 'Λανθασμένο όνομα χρήστη ή κωδικός πρόσβασης.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Σύνδεση — Σύστημα Διπλωματικών</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <img src="media/logo.svg" alt="Λογότυπο" class="logo">
    <h1>Σύστημα Υποστήριξης Διπλωματικών Εργασιών</h1>
</header>

<div class="login-wrapper">
    <div class="login-box">
        <h2>Σύνδεση</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <?php require_once __DIR__ . '/includes/functions.php'; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Όνομα Χρήστη (email)</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Κωδικός Πρόσβασης</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Σύνδεση</button>
        </form>
        <p style="font-size:0.8rem;color:var(--color-muted);margin-top:14px;">
            Demo λογαριασμοί (βλ. db/seed_users.php): π.χ. <em>stud1@upnet.gr</em>,
            <em>prof1@upatras.gr</em>, <em>secretary@upatras.gr</em> — κωδικός: <em>password123</em>
        </p>
    </div>
</div>

<footer class="site-footer">
    <p>&copy; 2025 Τμήμα Μηχανικών Η/Υ &amp; Πληροφορικής — Πανεπιστήμιο Πατρών</p>
</footer>
</body>
</html>
