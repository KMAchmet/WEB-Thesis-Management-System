<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('secretary');

$message = '';
$importedCount = 0;

/**
 * Expected JSON format (array of objects):
 * [
 *   {"role":"student","full_name":"...","email":"...","am":"...","address":"...","mobile_phone":"...","home_phone":"..."},
 *   {"role":"professor","full_name":"...","email":"...","department":"..."}
 * ]
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
    $content = file_get_contents($_FILES['json_file']['tmp_name']);
    $data = json_decode($content, true);

    if (!is_array($data)) {
        $message = 'Μη έγκυρο αρχείο JSON.';
    } else {
        $pdo->beginTransaction();
        try {
            foreach ($data as $entry) {
                $email = trim($entry['email'] ?? '');
                $fullName = trim($entry['full_name'] ?? '');
                $role = $entry['role'] ?? '';
                if ($email === '' || $fullName === '' || !in_array($role, ['student', 'professor'])) {
                    continue;
                }
                // skip duplicates
                $exists = $pdo->prepare("SELECT id FROM users WHERE email = :e");
                $exists->execute([':e' => $email]);
                if ($exists->fetch()) continue;

                $randomPassword = password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
                $pdo->prepare(
                    "INSERT INTO users (username, password_hash, role, full_name, email) VALUES (:u,:p,:r,:n,:e)"
                )->execute([':u' => $email, ':p' => $randomPassword, ':r' => $role, ':n' => $fullName, ':e' => $email]);
                $userId = $pdo->lastInsertId();

                if ($role === 'student') {
                    $pdo->prepare(
                        "INSERT INTO students (user_id, am, address, mobile_phone, home_phone) VALUES (:id,:am,:a,:m,:h)"
                    )->execute([
                        ':id' => $userId,
                        ':am' => $entry['am'] ?? uniqid(),
                        ':a' => $entry['address'] ?? '',
                        ':m' => $entry['mobile_phone'] ?? '',
                        ':h' => $entry['home_phone'] ?? '',
                    ]);
                } else {
                    $pdo->prepare("INSERT INTO professors (user_id, department) VALUES (:id, :d)")
                        ->execute([':id' => $userId, ':d' => $entry['department'] ?? 'ΤΜΗΥΠ']);
                }
                $importedCount++;
            }
            $pdo->commit();
            $message = "Εισήχθησαν επιτυχώς $importedCount νέες εγγραφές.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Σφάλμα εισαγωγής: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Εισαγωγή Δεδομένων — Γραμματεία</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/secretary.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Εισαγωγή Δεδομένων Φοιτητών / Διδασκόντων (JSON)</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card">
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="json_file">Αρχείο JSON</label>
                <input type="file" id="json_file" name="json_file" accept="application/json" required>
            </div>
            <button type="submit" class="btn btn-primary">Εισαγωγή</button>
        </form>
        <p style="font-size:0.85rem;color:var(--color-muted);margin-top:12px;">
            Νέες εγγραφές δεν έχουν username/password· δημιουργείται αυτόματα username = email
            και τυχαίος κωδικός πρόσβασης (κατά τη δημιουργία). Δείτε
            <a href="http://usidas.ceid.upatras.gr/web/2024/index.php" target="_blank">εδώ</a>
            για δείγματα δεδομένων.
        </p>
    </div>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
