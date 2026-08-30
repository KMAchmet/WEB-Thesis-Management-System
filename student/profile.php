<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$studentId = current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $mobile  = trim($_POST['mobile_phone'] ?? '');
    $home    = trim($_POST['home_phone'] ?? '');
    $email   = trim($_POST['email'] ?? '');

    $pdo->prepare("UPDATE students SET address = :a, mobile_phone = :m, home_phone = :h WHERE user_id = :id")
        ->execute([':a' => $address, ':m' => $mobile, ':h' => $home, ':id' => $studentId]);

    if ($email !== '') {
        $pdo->prepare("UPDATE users SET email = :e WHERE id = :id")
            ->execute([':e' => $email, ':id' => $studentId]);
    }
    $message = 'Τα στοιχεία ενημερώθηκαν επιτυχώς.';
}

$stmt = $pdo->prepare(
    "SELECT u.full_name, u.email, s.am, s.address, s.mobile_phone, s.home_phone
     FROM users u JOIN students s ON s.user_id = u.id WHERE u.id = :id"
);
$stmt->execute([':id' => $studentId]);
$profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Επεξεργασία Προφίλ — Φοιτητής</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/student.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Επεξεργασία Στοιχείων Επικοινωνίας</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card">
        <p><strong>Ονοματεπώνυμο:</strong> <?= h($profile['full_name']) ?></p>
        <p><strong>Αριθμός Μητρώου (ΑΜ):</strong> <?= h($profile['am']) ?></p>

        <form method="post">
            <div class="form-group">
                <label for="email">Email επικοινωνίας</label>
                <input type="email" id="email" name="email" value="<?= h($profile['email']) ?>">
            </div>
            <div class="form-group">
                <label for="address">Πλήρης Ταχυδρομική Διεύθυνση</label>
                <input type="text" id="address" name="address" value="<?= h($profile['address']) ?>">
            </div>
            <div class="form-group">
                <label for="mobile_phone">Κινητό Τηλέφωνο</label>
                <input type="text" id="mobile_phone" name="mobile_phone" value="<?= h($profile['mobile_phone']) ?>">
            </div>
            <div class="form-group">
                <label for="home_phone">Σταθερό Τηλέφωνο</label>
                <input type="text" id="home_phone" name="home_phone" value="<?= h($profile['home_phone']) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Αποθήκευση</button>
        </form>
    </div>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
