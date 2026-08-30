<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();
$thesisId = (int)($_GET['id'] ?? $_POST['thesis_id'] ?? 0);
$message = '';

$stmt = $pdo->prepare("SELECT t.*, tp.title, u.full_name AS student_name FROM theses t
                        JOIN topics tp ON tp.id = t.topic_id
                        JOIN students s ON s.user_id = t.student_id
                        JOIN users u ON u.id = s.user_id
                        WHERE t.id = :id");
$stmt->execute([':id' => $thesisId]);
$thesis = $stmt->fetch();
if (!$thesis) { die('Δεν βρέθηκε η διπλωματική.'); }

$isSupervisor = ($thesis['supervisor_id'] == $profId);

// ---- Cancel assignment while "Υπό Ανάθεση" (supervisor only) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_under_assignment']) && $isSupervisor) {
    $pdo->prepare("CALL sp_cancel_topic_assignment(:id)")->execute([':id' => $thesisId]);
    $message = 'Η ανάθεση ακυρώθηκε.';
}

// ---- Add note (any role in the committee, up to 300 chars, private) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['note_text'])) {
    $note = mb_substr(trim($_POST['note_text']), 0, 300);
    if ($note !== '') {
        $pdo->prepare("INSERT INTO notes (thesis_id, professor_id, note_text) VALUES (:t,:p,:n)")
            ->execute([':t' => $thesisId, ':p' => $profId, ':n' => $note]);
        $message = 'Η σημείωση καταχωρήθηκε.';
    }
}

// ---- Cancel active thesis (supervisor only, after 2 years, needs GA number/year) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_active']) && $isSupervisor) {
    $twoYearsAgo = date('Y-m-d', strtotime('-2 years'));
    if ($thesis['assigned_at'] && $thesis['assigned_at'] <= $twoYearsAgo) {
        $pdo->prepare(
            "UPDATE theses SET status='cancelled', cancel_ga_number=:n, cancel_ga_year=:y, cancel_reason='από Διδάσκοντα' WHERE id=:id"
        )->execute([':n' => $_POST['ga_number'], ':y' => $_POST['ga_year'], ':id' => $thesisId]);
        $message = 'Η διπλωματική ακυρώθηκε.';
    } else {
        $message = 'Δεν έχουν παρέλθει 2 έτη από την οριστική ανάθεση — δεν επιτρέπεται η ακύρωση.';
    }
}

// ---- Move to "under_examination" (supervisor only) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_to_exam']) && $isSupervisor) {
    $pdo->prepare("UPDATE theses SET status='under_examination' WHERE id=:id")->execute([':id' => $thesisId]);
    $message = 'Η κατάσταση άλλαξε σε «Υπό Εξέταση».';
}

// ---- Register grade (any accepted committee member) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade'])) {
    $grade = (float)$_POST['grade'];
    $criteria = json_encode($_POST['criteria'] ?? [], JSON_UNESCAPED_UNICODE);
    $pdo->prepare(
        "UPDATE committee_members SET grade=:g, grade_criteria_json=:c, graded_at=NOW()
         WHERE thesis_id=:t AND professor_id=:p"
    )->execute([':g' => $grade, ':c' => $criteria, ':t' => $thesisId, ':p' => $profId]);
    $message = 'Ο βαθμός καταχωρήθηκε.';
}

// refresh thesis after any update
$stmt = $pdo->prepare("SELECT * FROM theses WHERE id = :id");
$stmt->execute([':id' => $thesisId]);
$thesisFresh = $stmt->fetch();
$thesis = array_merge($thesis, $thesisFresh);

// committee (for "under_assignment" view + grading view)
$cstmt = $pdo->prepare(
    "SELECT cm.*, u.full_name FROM committee_members cm JOIN users u ON u.id = cm.professor_id
     WHERE cm.thesis_id = :id ORDER BY cm.member_role DESC"
);
$cstmt->execute([':id' => $thesisId]);
$committee = $cstmt->fetchAll();

// my notes on this thesis
$nstmt = $pdo->prepare("SELECT * FROM notes WHERE thesis_id=:t AND professor_id=:p ORDER BY created_at DESC");
$nstmt->execute([':t' => $thesisId, ':p' => $profId]);
$myNotes = $nstmt->fetchAll();

$myGrade = null;
foreach ($committee as $c) {
    if ($c['professor_id'] == $profId) { $myGrade = $c; break; }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Διαχείριση Διπλωματικής — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2><?= h($thesis['title']) ?></h2>
    <p><span class="<?= status_class($thesis['status']) ?>"><?= status_label($thesis['status']) ?></span>
       — Φοιτητής: <?= h($thesis['student_name']) ?> —
       Ρόλος μου: <?= $isSupervisor ? 'Επιβλέπων' : 'Μέλος Τριμελούς' ?></p>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <?php if ($thesis['status'] === 'under_assignment'): ?>
        <div class="card">
            <h3>Κατάσταση Προσκλήσεων Τριμελούς</h3>
            <table>
                <thead><tr><th>Διδάσκων</th><th>Ρόλος</th><th>Πρόσκληση</th><th>Αποδοχή/Απόρριψη</th></tr></thead>
                <tbody>
                <?php foreach ($committee as $c): ?>
                    <tr>
                        <td data-label="Διδάσκων"><?= h($c['full_name']) ?></td>
                        <td data-label="Ρόλος"><?= $c['member_role'] === 'supervisor' ? 'Επιβλέπων' : 'Μέλος' ?></td>
                        <td data-label="Πρόσκληση"><?= h($c['invited_at']) ?></td>
                        <td data-label="Απάντηση"><?= h($c['responded_at'] ?? '—') . ' (' . $c['invitation_status'] . ')' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($isSupervisor): ?>
                <form method="post" style="margin-top:14px;">
                    <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                    <button type="submit" name="cancel_under_assignment" class="btn btn-danger"
                            onclick="return confirm('Σίγουρα θέλετε να ακυρώσετε την ανάθεση;');">
                        Ακύρωση Ανάθεσης
                    </button>
                </form>
            <?php endif; ?>
        </div>

    <?php elseif ($thesis['status'] === 'active'): ?>
        <div class="card">
            <h3>Ιδιωτικές Σημειώσεις</h3>
            <form method="post">
                <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                <div class="form-group">
                    <textarea name="note_text" maxlength="300" rows="2" placeholder="Μέχρι 300 χαρακτήρες..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-small">Προσθήκη Σημείωσης</button>
            </form>
            <ul>
                <?php foreach ($myNotes as $n): ?>
                    <li><?= h($n['note_text']) ?> <small>(<?= h($n['created_at']) ?>)</small></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($isSupervisor): ?>
        <div class="card">
            <h3>Ενέργειες Επιβλέποντα</h3>
            <form method="post" style="margin-bottom:16px;">
                <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                <button type="submit" name="move_to_exam" class="btn btn-primary">Αλλαγή σε «Υπό Εξέταση»</button>
            </form>

            <form method="post" onsubmit="return confirm('Σίγουρα θέλετε ακύρωση;');">
                <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                <p>Ακύρωση επιτρέπεται μόνο 2 έτη μετά την οριστική ανάθεση (<?= h($thesis['assigned_at']) ?>).</p>
                <div class="form-group"><label>Αριθμός Γ.Σ.</label><input type="text" name="ga_number"></div>
                <div class="form-group"><label>Έτος Γ.Σ.</label><input type="number" name="ga_year"></div>
                <button type="submit" name="cancel_active" class="btn btn-danger">Ακύρωση Ανάθεσης</button>
            </form>
        </div>
        <?php endif; ?>

    <?php elseif ($thesis['status'] === 'under_examination'): ?>
        <div class="card">
            <h3>Πρόχειρο Κείμενο Φοιτητή</h3>
            <?php if ($thesis['draft_file_path']): ?>
                <p><a href="<?= h('..' . $thesis['draft_file_path']) ?>" target="_blank">Λήψη Πρόχειρου Κειμένου</a></p>
            <?php else: ?>
                <p>Δεν έχει αναρτηθεί ακόμα κείμενο.</p>
            <?php endif; ?>
        </div>

        <?php if ($isSupervisor && $thesis['exam_datetime']): ?>
        <div class="card">
            <h3>Ανακοίνωση Παρουσίασης</h3>
            <a class="btn btn-secondary" href="announcement.php?id=<?= (int)$thesisId ?>" target="_blank">
                Προβολή / Παραγωγή Ανακοίνωσης
            </a>
        </div>
        <?php endif; ?>

        <div class="card">
            <h3>Καταχώρηση Βαθμού</h3>
            <?php if ($myGrade && $myGrade['grade'] !== null): ?>
                <p>✅ Έχετε ήδη καταχωρήσει βαθμό: <strong><?= h($myGrade['grade']) ?></strong></p>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                    <div class="form-group"><label>Επιστημονική Επάρκεια (0-10)</label><input type="number" step="0.5" min="0" max="10" name="criteria[scientific]"></div>
                    <div class="form-group"><label>Ποιότητα Κειμένου (0-10)</label><input type="number" step="0.5" min="0" max="10" name="criteria[writing]"></div>
                    <div class="form-group"><label>Παρουσίαση (0-10)</label><input type="number" step="0.5" min="0" max="10" name="criteria[presentation]"></div>
                    <div class="form-group"><label>Τελικός Βαθμός (0-10)</label><input type="number" step="0.1" min="0" max="10" name="grade" required></div>
                    <button type="submit" class="btn btn-primary">Καταχώρηση Βαθμού</button>
                </form>
            <?php endif; ?>

            <h4>Βαθμοί Μελών</h4>
            <table>
                <thead><tr><th>Μέλος</th><th>Βαθμός</th></tr></thead>
                <tbody>
                <?php foreach ($committee as $c): ?>
                    <tr><td data-label="Μέλος"><?= h($c['full_name']) ?></td><td data-label="Βαθμός"><?= h($c['grade'] ?? '—') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="card">
            <p>Τελικός Βαθμός: <strong><?= h($thesis['final_grade'] ?? '—') ?></strong></p>
            <p><a class="btn btn-secondary" href="../student/exam_report.php?id=<?= (int)$thesisId ?>" target="_blank">Προβολή Πρακτικού</a></p>
        </div>
    <?php endif; ?>

    <p><a href="thesis_list.php">&larr; Επιστροφή στη λίστα</a></p>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
