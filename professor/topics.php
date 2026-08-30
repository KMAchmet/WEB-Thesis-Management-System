<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();
$message = '';

// Create new topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_topic'])) {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['short_description'] ?? '');
    $pdfPath = null;

    if (!empty($_FILES['pdf_file']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/topics/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid('topic_') . '_' . basename($_FILES['pdf_file']['name']);
        move_uploaded_file($_FILES['pdf_file']['tmp_name'], $uploadDir . $filename);
        $pdfPath = '/uploads/topics/' . $filename;
    }

    if ($title !== '' && $desc !== '') {
        $pdo->prepare(
            "INSERT INTO topics (professor_id, title, short_description, pdf_path) VALUES (:p,:t,:d,:f)"
        )->execute([':p' => $profId, ':t' => $title, ':d' => $desc, ':f' => $pdfPath]);
        $message = 'Το θέμα καταχωρήθηκε επιτυχώς.';
    }
}

// Edit existing topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_topic_id'])) {
    $id = (int)$_POST['edit_topic_id'];
    $title = trim($_POST['edit_title'] ?? '');
    $desc  = trim($_POST['edit_description'] ?? '');
    $pdo->prepare("UPDATE topics SET title = :t, short_description = :d WHERE id = :id AND professor_id = :p")
        ->execute([':t' => $title, ':d' => $desc, ':id' => $id, ':p' => $profId]);
    $message = 'Το θέμα ενημερώθηκε.';
}

$stmt = $pdo->prepare("SELECT * FROM topics WHERE professor_id = :p ORDER BY created_at DESC");
$stmt->execute([':p' => $profId]);
$topics = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Θέματα προς Ανάθεση — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Προβολή &amp; Δημιουργία Θεμάτων προς Ανάθεση</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card">
        <h3>Νέο Θέμα</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Τίτλος</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="short_description">Σύνοψη</label>
                <textarea id="short_description" name="short_description" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="pdf_file">Αναλυτική Παρουσίαση (PDF)</label>
                <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf">
            </div>
            <button type="submit" name="create_topic" class="btn btn-primary">Δημιουργία Θέματος</button>
        </form>
    </div>

    <div class="card">
        <h3>Τα Θέματά μου</h3>
        <table>
            <thead><tr><th>Τίτλος</th><th>Κατάσταση</th><th>Ενέργειες</th></tr></thead>
            <tbody>
            <?php foreach ($topics as $t): ?>
                <tr>
                    <td data-label="Τίτλος"><?= h($t['title']) ?></td>
                    <td data-label="Κατάσταση">
                        <?= $t['is_taken'] ? '<span class="badge badge-completed">Ανατεθειμένο</span>'
                                            : '<span class="badge badge-active">Ελεύθερο</span>' ?>
                    </td>
                    <td data-label="Ενέργειες">
                        <button class="btn btn-small btn-secondary" onclick="toggleEdit(<?= (int)$t['id'] ?>)">Επεξεργασία</button>
                    </td>
                </tr>
                <tr id="edit-row-<?= (int)$t['id'] ?>" style="display:none;">
                    <td colspan="3">
                        <form method="post">
                            <input type="hidden" name="edit_topic_id" value="<?= (int)$t['id'] ?>">
                            <div class="form-group">
                                <label>Τίτλος</label>
                                <input type="text" name="edit_title" value="<?= h($t['title']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Σύνοψη</label>
                                <textarea name="edit_description" rows="2"><?= h($t['short_description']) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-small">Αποθήκευση</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleEdit(id) {
    const row = document.getElementById('edit-row-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
