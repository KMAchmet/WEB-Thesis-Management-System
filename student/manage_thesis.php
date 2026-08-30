<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$studentId = current_user_id();
$message = '';

$stmt = $pdo->prepare("SELECT * FROM theses WHERE student_id = :sid ORDER BY created_at DESC LIMIT 1");
$stmt->execute([':sid' => $studentId]);
$thesis = $stmt->fetch();

if (!$thesis) {
    header('Location: index.php');
    exit;
}

// -------- Action: invite a committee member (Υπό Ανάθεση) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_professor_id'])) {
    $profId = (int)$_POST['invite_professor_id'];
    $exists = $pdo->prepare("SELECT COUNT(*) FROM committee_members WHERE thesis_id = :t AND professor_id = :p");
    $exists->execute([':t' => $thesis['id'], ':p' => $profId]);
    if ($exists->fetchColumn() == 0) {
        $pdo->prepare("CALL sp_invite_committee_member(:t, :p)")
            ->execute([':t' => $thesis['id'], ':p' => $profId]);
        $message = 'Η πρόσκληση στάλθηκε επιτυχώς.';
    } else {
        $message = 'Ο διδάσκων έχει ήδη προσκληθεί.';
    }
}

// -------- Action: upload draft + material links (Υπό Εξέταση) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_draft'])) {
    $link = trim($_POST['material_link'] ?? '');
    if ($link !== '') {
        $pdo->prepare("INSERT INTO materials (thesis_id, material_type, label, path_or_url) VALUES (:t,'link',:l,:l)")
            ->execute([':t' => $thesis['id'], ':l' => $link]);
        $message = 'Ο σύνδεσμος υλικού προστέθηκε.';
    }
    if (!empty($_FILES['draft_file']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/drafts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = 'thesis_' . $thesis['id'] . '_' . basename($_FILES['draft_file']['name']);
        move_uploaded_file($_FILES['draft_file']['tmp_name'], $uploadDir . $filename);
        $pdo->prepare("UPDATE theses SET draft_file_path = :p WHERE id = :id")
            ->execute([':p' => '/uploads/drafts/' . $filename, ':id' => $thesis['id']]);
        $message = 'Το πρόχειρο κείμενο ανέβηκε επιτυχώς.';
    }
}

// -------- Action: set exam date/mode (Υπό Εξέταση) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_exam'])) {
    $datetime = $_POST['exam_datetime'] ?? null;
    $mode = $_POST['exam_mode'] ?? 'in_person';
    $location = trim($_POST['exam_location'] ?? '');
    $link = trim($_POST['exam_link'] ?? '');
    $pdo->prepare(
        "UPDATE theses SET exam_datetime = :dt, exam_mode = :m, exam_location = :loc, exam_link = :lnk WHERE id = :id"
    )->execute([':dt' => $datetime, ':m' => $mode, ':loc' => $location, ':lnk' => $link, ':id' => $thesis['id']]);
    $message = 'Τα στοιχεία εξέτασης καταχωρήθηκαν.';
}

// -------- Action: save nemertes link (Υπό Εξέταση, after grading) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nemertes_link'])) {
    $link = trim($_POST['nemertes_link']);
    $pdo->prepare("UPDATE theses SET nemertes_link = :l WHERE id = :id")
        ->execute([':l' => $link, ':id' => $thesis['id']]);
    $message = 'Ο σύνδεσμος του Νημερτή καταχωρήθηκε.';
}

// refresh thesis row after potential update
$stmt = $pdo->prepare("SELECT * FROM theses WHERE id = :id");
$stmt->execute([':id' => $thesis['id']]);
$thesis = $stmt->fetch();

// professors not yet invited (for invite dropdown)
$profStmt = $pdo->prepare(
    "SELECT u.id, u.full_name FROM users u JOIN professors p ON p.user_id = u.id
     WHERE u.id != :sup AND u.id NOT IN (SELECT professor_id FROM committee_members WHERE thesis_id = :t)"
);
$profStmt->execute([':sup' => $thesis['supervisor_id'], ':t' => $thesis['id']]);
$availableProfessors = $profStmt->fetchAll();

$allGraded = false;
if ($thesis['status'] === 'under_examination') {
    $gradeCheck = $pdo->prepare(
        "SELECT COUNT(*) total, SUM(grade IS NOT NULL) graded FROM committee_members
         WHERE thesis_id = :t AND invitation_status = 'accepted'"
    );
    $gradeCheck->execute([':t' => $thesis['id']]);
    $g = $gradeCheck->fetch();
    $allGraded = $g['total'] > 0 && $g['total'] == $g['graded'];
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Διαχείριση Διπλωματικής — Φοιτητής</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/student.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Διαχείριση Διπλωματικής Εργασίας</h2>
    <p><span class="<?= status_class($thesis['status']) ?>"><?= h(status_label($thesis['status'])) ?></span></p>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <?php if ($thesis['status'] === 'under_assignment'): ?>
        <div class="card">
            <h3>Πρόσκληση Μελών Τριμελούς Επιτροπής</h3>
            <p>Χρειάζονται 2 ακόμα μέλη Διδάσκοντες να αποδεχθούν την πρόσκληση για να ενεργοποιηθεί
               η διπλωματική.</p>
            <form method="post">
                <div class="form-group">
                    <label for="invite_professor_id">Επιλέξτε Διδάσκοντα</label>
                    <select name="invite_professor_id" id="invite_professor_id" required>
                        <option value="">-- Επιλέξτε --</option>
                        <?php foreach ($availableProfessors as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= h($p['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Αποστολή Πρόσκλησης</button>
            </form>
            <div id="committee-status" style="margin-top:16px;"></div>
        </div>

    <?php elseif ($thesis['status'] === 'active'): ?>
        <div class="card">
            <p>Η διπλωματική σας βρίσκεται σε εξέλιξη. Όταν ολοκληρώσετε τη συγγραφή, ο επιβλέπων
               θα αλλάξει την κατάσταση σε «Υπό Εξέταση» ώστε να μπορέσετε να αναρτήσετε το κείμενό σας.</p>
        </div>

    <?php elseif ($thesis['status'] === 'under_examination'): ?>
        <div class="card">
            <h3>Ανάρτηση Πρόχειρου Κειμένου &amp; Υλικού</h3>
            <?php if ($thesis['draft_file_path']): ?>
                <p>✅ Τρέχον αρχείο: <a href="<?= h('..' . $thesis['draft_file_path']) ?>" target="_blank">Λήψη</a></p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="draft_file">Αρχείο Πρόχειρου Κειμένου (PDF)</label>
                    <input type="file" id="draft_file" name="draft_file" accept="application/pdf">
                </div>
                <div class="form-group">
                    <label for="material_link">Σύνδεσμος Υποστηρικτικού Υλικού (Drive, YouTube κλπ)</label>
                    <input type="text" id="material_link" name="material_link" placeholder="https://...">
                </div>
                <button type="submit" name="submit_draft" class="btn btn-primary">Αποθήκευση</button>
            </form>
        </div>

        <div class="card">
            <h3>Στοιχεία Παρουσίασης</h3>
            <form method="post">
                <div class="form-group">
                    <label for="exam_datetime">Ημερομηνία &amp; Ώρα Εξέτασης</label>
                    <input type="datetime-local" id="exam_datetime" name="exam_datetime"
                           value="<?= $thesis['exam_datetime'] ? date('Y-m-d\TH:i', strtotime($thesis['exam_datetime'])) : '' ?>">
                </div>
                <div class="form-group">
                    <label for="exam_mode">Τρόπος Εξέτασης</label>
                    <select id="exam_mode" name="exam_mode">
                        <option value="in_person" <?= $thesis['exam_mode'] === 'in_person' ? 'selected' : '' ?>>Δια ζώσης</option>
                        <option value="online" <?= $thesis['exam_mode'] === 'online' ? 'selected' : '' ?>>Διαδικτυακά</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="exam_location">Αίθουσα (αν δια ζώσης)</label>
                    <input type="text" id="exam_location" name="exam_location" value="<?= h($thesis['exam_location'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="exam_link">Σύνδεσμος Παρακολούθησης (αν διαδικτυακά)</label>
                    <input type="text" id="exam_link" name="exam_link" value="<?= h($thesis['exam_link'] ?? '') ?>">
                </div>
                <button type="submit" name="set_exam" class="btn btn-primary">Καταχώρηση</button>
            </form>
        </div>

        <?php if ($allGraded): ?>
        <div class="card">
            <h3>Πρακτικό Εξέτασης &amp; Νημερτής</h3>
            <p><a class="btn btn-secondary" href="exam_report.php?id=<?= (int)$thesis['id'] ?>" target="_blank">
                Προβολή Πρακτικού Εξέτασης (HTML)</a></p>
            <form method="post">
                <div class="form-group">
                    <label for="nemertes_link">Σύνδεσμος Αποθετηρίου Νημερτής</label>
                    <input type="text" id="nemertes_link" name="nemertes_link" value="<?= h($thesis['nemertes_link'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Αποθήκευση</button>
            </form>
        </div>
        <?php endif; ?>

    <?php elseif ($thesis['status'] === 'completed'): ?>
        <div class="card">
            <h3>Διπλωματική Ολοκληρωμένη</h3>
            <p><strong>Τελικός Βαθμός:</strong> <?= h($thesis['final_grade'] ?? '—') ?></p>
            <p><a class="btn btn-secondary" href="exam_report.php?id=<?= (int)$thesis['id'] ?>" target="_blank">
                Προβολή Πρακτικού Εξέτασης</a></p>
        </div>
    <?php endif; ?>
</div>

<script>
// AJAX refresh of committee invitation status (used while "Υπό Ανάθεση")
function refreshCommitteeStatus() {
    const box = document.getElementById('committee-status');
    if (!box) return;
    fetch('ajax_get_thesis.php?thesis_id=<?= (int)$thesis['id'] ?>')
        .then(r => r.json())
        .then(data => {
            if (!data.committee) return;
            let html = '<table><thead><tr><th>Διδάσκων</th><th>Κατάσταση</th></tr></thead><tbody>';
            data.committee.forEach(c => {
                html += `<tr><td data-label="Διδάσκων">${c.full_name}</td><td data-label="Κατάσταση">${c.invitation_status}</td></tr>`;
            });
            html += '</tbody></table>';
            box.innerHTML = html;
        });
}
document.addEventListener('DOMContentLoaded', () => {
    refreshCommitteeStatus();
    setInterval(refreshCommitteeStatus, 15000);
});
</script>

<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
