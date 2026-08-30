<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('secretary');

$stmt = $pdo->query(
    "SELECT t.*, tp.title, u.full_name AS student_name, up.full_name AS supervisor_name
     FROM theses t
     JOIN topics tp ON tp.id = t.topic_id
     JOIN students s ON s.user_id = t.student_id
     JOIN users u ON u.id = s.user_id
     JOIN professors p ON p.user_id = t.supervisor_id
     JOIN users up ON up.id = p.user_id
     WHERE t.status IN ('active','under_examination')
     ORDER BY t.created_at DESC"
);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Προβολή ΔΕ — Γραμματεία</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/secretary.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Ενεργές &amp; Υπό Εξέταση Διπλωματικές Εργασίες</h2>
    <div class="card">
        <table>
            <thead><tr><th>Τίτλος</th><th>Φοιτητής</th><th>Επιβλέπων</th><th>Κατάσταση</th><th>Χρόνος από ανάθεση</th><th>Ενέργειες</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td data-label="Τίτλος"><?= h($r['title']) ?></td>
                    <td data-label="Φοιτητής"><?= h($r['student_name']) ?></td>
                    <td data-label="Επιβλέπων"><?= h($r['supervisor_name']) ?></td>
                    <td data-label="Κατάσταση"><span class="<?= status_class($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
                    <td data-label="Χρόνος"><?= h(elapsed_since($r['assigned_at'])) ?></td>
                    <td data-label="Ενέργειες"><a class="btn btn-small btn-primary" href="manage_thesis.php?id=<?= (int)$r['id'] ?>">Διαχείριση</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6">Δεν υπάρχουν εγγραφές.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
