<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cmId = (int)($_POST['committee_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['accepted', 'rejected'])) {
        $pdo->prepare(
            "UPDATE committee_members SET invitation_status=:a, responded_at=NOW()
             WHERE id=:id AND professor_id=:p AND invitation_status='pending'"
        )->execute([':a' => $action, ':id' => $cmId, ':p' => $profId]);
        $message = $action === 'accepted' ? 'Αποδεχθήκατε την πρόσκληση.' : 'Απορρίψατε την πρόσκληση.';
    }
}

$stmt = $pdo->prepare(
    "SELECT cm.id AS committee_id, tp.title, u.full_name AS student_name, cm.invited_at
     FROM committee_members cm
     JOIN theses t ON t.id = cm.thesis_id
     JOIN topics tp ON tp.id = t.topic_id
     JOIN students s ON s.user_id = t.student_id
     JOIN users u ON u.id = s.user_id
     WHERE cm.professor_id = :p AND cm.invitation_status = 'pending'
     ORDER BY cm.invited_at DESC"
);
$stmt->execute([':p' => $profId]);
$invites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Προσκλήσεις Τριμελούς — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Προσκλήσεις Συμμετοχής σε Τριμελή Επιτροπή</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card">
        <table>
            <thead><tr><th>Τίτλος Διπλωματικής</th><th>Φοιτητής</th><th>Ημ/νία Πρόσκλησης</th><th>Ενέργειες</th></tr></thead>
            <tbody>
            <?php foreach ($invites as $i): ?>
                <tr>
                    <td data-label="Τίτλος"><?= h($i['title']) ?></td>
                    <td data-label="Φοιτητής"><?= h($i['student_name']) ?></td>
                    <td data-label="Ημ/νία"><?= h($i['invited_at']) ?></td>
                    <td data-label="Ενέργειες">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="committee_id" value="<?= (int)$i['committee_id'] ?>">
                            <button type="submit" name="action" value="accepted" class="btn btn-small btn-primary">Αποδοχή</button>
                            <button type="submit" name="action" value="rejected" class="btn btn-small btn-danger">Απόρριψη</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$invites): ?>
                <tr><td colspan="4">Δεν υπάρχουν εκκρεμείς προσκλήσεις.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
