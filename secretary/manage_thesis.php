<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('secretary');

$thesisId = (int)($_GET['id'] ?? $_POST['thesis_id'] ?? 0);
$message = '';

$stmt = $pdo->prepare(
    "SELECT t.*, tp.title, u.full_name AS student_name FROM theses t
     JOIN topics tp ON tp.id = t.topic_id
     JOIN students s ON s.user_id = t.student_id
     JOIN users u ON u.id = s.user_id
     WHERE t.id = :id"
);
$stmt->execute([':id' => $thesisId]);
$thesis = $stmt->fetch();
if (!$thesis) { die('Δεν βρέθηκε.'); }

// ---- register GA number/year (Ενεργή) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_ga'])) {
    $pdo->prepare("UPDATE theses SET ga_number=:n, ga_year=:y WHERE id=:id")
        ->execute([':n' => $_POST['ga_number'], ':y' => $_POST['ga_year'], ':id' => $thesisId]);
    $message = 'Ο ΑΠ Γ.Σ. καταχωρήθηκε.';
}

// ---- cancel assignment (Ενεργή) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_thesis'])) {
    $pdo->prepare(
        "UPDATE theses SET status='cancelled', cancel_ga_number=:n, cancel_ga_year=:y, cancel_reason=:r WHERE id=:id"
    )->execute([
        ':n' => $_POST['cancel_ga_number'], ':y' => $_POST['cancel_ga_year'],
        ':r' => $_POST['cancel_reason'], ':id' => $thesisId,
    ]);
    $message = 'Η διπλωματική ακυρώθηκε.';
}

// ---- mark completed (Υπό Εξέταση -> Περατωμένη) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_completed'])) {
    if ($thesis['final_grade'] !== null && $thesis['nemertes_link']) {
        $pdo->prepare("UPDATE theses SET status='completed' WHERE id=:id")->execute([':id' => $thesisId]);
        $message = 'Η διπλωματική χαρακτηρίστηκε ως Περατωμένη.';
    } else {
        $message = 'Δεν μπορεί να ολοκληρωθεί: λείπει ο τελικός βαθμός ή/και ο σύνδεσμος Νημερτή.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM theses WHERE id=:id");
$stmt->execute([':id' => $thesisId]);
$thesisFresh = $stmt->fetch();
$thesis = array_merge($thesis, $thesisFresh);
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Διαχείριση ΔΕ — Γραμματεία</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/secretary.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2><?= h($thesis['title']) ?></h2>
    <p><span class="<?= status_class($thesis['status']) ?>"><?= status_label($thesis['status']) ?></span> —
       Φοιτητής: <?= h($thesis['student_name']) ?></p>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <?php if ($thesis['status'] === 'active'): ?>
        <div class="card">
            <h3>Καταχώρηση ΑΠ Γενικής Συνέλευσης</h3>
            <form method="post">
                <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                <div class="form-group"><label>Αριθμός Πρωτοκόλλου</label><input type="text" name="ga_number" value="<?= h($thesis['ga_number'] ?? '') ?>"></div>
                <div class="form-group"><label>Έτος</label><input type="number" name="ga_year" value="<?= h($thesis['ga_year'] ?? '') ?>"></div>
                <button type="submit" name="register_ga" class="btn btn-primary">Καταχώρηση</button>
            </form>
        </div>

        <div class="card">
            <h3>Ακύρωση Ανάθεσης</h3>
            <form method="post" onsubmit="return confirm('Σίγουρα;');">
                <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                <div class="form-group"><label>Αριθμός Γ.Σ. Ακύρωσης</label><input type="text" name="cancel_ga_number"></div>
                <div class="form-group"><label>Έτος</label><input type="number" name="cancel_ga_year"></div>
                <div class="form-group"><label>Λόγος Ακύρωσης</label><input type="text" name="cancel_reason" placeholder="π.χ. κατόπιν αίτησης φοιτητή/τριας"></div>
                <button type="submit" name="cancel_thesis" class="btn btn-danger">Ακύρωση</button>
            </form>
        </div>

    <?php elseif ($thesis['status'] === 'under_examination'): ?>
        <div class="card">
            <p><strong>Τελικός Βαθμός:</strong> <?= h($thesis['final_grade'] ?? 'εκκρεμεί') ?></p>
            <p><strong>Σύνδεσμος Νημερτή:</strong> <?= h($thesis['nemertes_link'] ?? 'εκκρεμεί') ?></p>
            <form method="post">
                <input type="hidden" name="thesis_id" value="<?= (int)$thesisId ?>">
                <button type="submit" name="mark_completed" class="btn btn-primary">Χαρακτηρισμός ως Περατωμένη</button>
            </form>
        </div>
    <?php else: ?>
        <div class="card"><p>Δεν υπάρχουν ενέργειες Γραμματείας για αυτήν την κατάσταση.</p></div>
    <?php endif; ?>

    <p><a href="index.php">&larr; Επιστροφή</a></p>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
