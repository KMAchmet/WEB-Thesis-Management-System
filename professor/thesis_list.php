<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();
$statusFilter = $_GET['status'] ?? '';
$roleFilter = $_GET['role'] ?? '';   // supervisor | member

$sql = "SELECT DISTINCT t.*, tp.title, u.full_name AS student_name,
               CASE WHEN t.supervisor_id = :p1 THEN 'supervisor' ELSE 'member' END AS my_role
        FROM theses t
        JOIN topics tp ON tp.id = t.topic_id
        JOIN students s ON s.user_id = t.student_id
        JOIN users u ON u.id = s.user_id
        LEFT JOIN committee_members cm ON cm.thesis_id = t.id AND cm.professor_id = :p2
        WHERE (t.supervisor_id = :p3 OR (cm.professor_id = :p4 AND cm.invitation_status = 'accepted'))";

$params = [':p1' => $profId, ':p2' => $profId, ':p3' => $profId, ':p4' => $profId];

if ($statusFilter) {
    $sql .= " AND t.status = :status";
    $params[':status'] = $statusFilter;
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

if ($roleFilter) {
    $rows = array_values(array_filter($rows, fn($r) => $r['my_role'] === $roleFilter));
}

// -------- Export --------
if (isset($_GET['export'])) {
    if ($_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=theses_export.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Τίτλος', 'Φοιτητής', 'Κατάσταση', 'Ρόλος', 'Τελικός Βαθμός']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['title'], $r['student_name'], status_label($r['status']), $r['my_role'], $r['final_grade']]);
        }
        fclose($out);
        exit;
    }
    if ($_GET['export'] === 'json') {
        json_response($rows);
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Λίστα Διπλωματικών — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Λίστα Διπλωματικών Εργασιών</h2>

    <div class="card">
        <form method="get" class="filter-form">
            <div class="form-group">
                <label>Κατάσταση</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Όλες</option>
                    <?php foreach (['under_assignment','active','under_examination','completed','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= status_label($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Ρόλος</label>
                <select name="role" onchange="this.form.submit()">
                    <option value="">Όλοι</option>
                    <option value="supervisor" <?= $roleFilter === 'supervisor' ? 'selected' : '' ?>>Επιβλέπων</option>
                    <option value="member" <?= $roleFilter === 'member' ? 'selected' : '' ?>>Μέλος Τριμελούς</option>
                </select>
            </div>
        </form>
        <a class="btn btn-secondary btn-small" href="?status=<?= h($statusFilter) ?>&role=<?= h($roleFilter) ?>&export=csv">Εξαγωγή CSV</a>
        <a class="btn btn-secondary btn-small" href="?status=<?= h($statusFilter) ?>&role=<?= h($roleFilter) ?>&export=json">Εξαγωγή JSON</a>
    </div>

    <div class="card">
        <table>
            <thead><tr><th>Τίτλος</th><th>Φοιτητής</th><th>Κατάσταση</th><th>Ρόλος</th><th>Ενέργειες</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td data-label="Τίτλος"><?= h($r['title']) ?></td>
                    <td data-label="Φοιτητής"><?= h($r['student_name']) ?></td>
                    <td data-label="Κατάσταση"><span class="<?= status_class($r['status']) ?>"><?= status_label($r['status']) ?></span></td>
                    <td data-label="Ρόλος"><?= $r['my_role'] === 'supervisor' ? 'Επιβλέπων' : 'Μέλος' ?></td>
                    <td data-label="Ενέργειες"><a class="btn btn-small btn-primary" href="manage_thesis.php?id=<?= (int)$r['id'] ?>">Διαχείριση</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="5">Δεν βρέθηκαν διπλωματικές.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
