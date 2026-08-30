<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$thesisId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT t.*, tp.title, u.full_name AS student_name, up.full_name AS supervisor_name
     FROM theses t
     JOIN topics tp ON tp.id = t.topic_id
     JOIN students s ON s.user_id = t.student_id
     JOIN users u ON u.id = s.user_id
     JOIN professors p ON p.user_id = t.supervisor_id
     JOIN users up ON up.id = p.user_id
     WHERE t.id = :id AND t.supervisor_id = :prof"
);
$stmt->execute([':id' => $thesisId, ':prof' => current_user_id()]);
$thesis = $stmt->fetch();
if (!$thesis) { die('Δεν βρέθηκε.'); }

$cstmt = $pdo->prepare(
    "SELECT u.full_name FROM committee_members cm JOIN users u ON u.id = cm.professor_id
     WHERE cm.thesis_id = :id AND cm.invitation_status='accepted' ORDER BY cm.member_role DESC"
);
$cstmt->execute([':id' => $thesisId]);
$members = array_column($cstmt->fetchAll(), 'full_name');
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<title>Ανακοίνωση Παρουσίασης</title>
<link rel="stylesheet" href="../css/style.css">
<style>body{padding:30px;} .ann{max-width:700px;margin:0 auto;border:1px solid #ccc;padding:26px;background:#fff;}</style>
</head>
<body>
<div class="ann">
    <h2 style="text-align:center;">ΑΝΑΚΟΙΝΩΣΗ ΠΑΡΟΥΣΙΑΣΗΣ ΔΙΠΛΩΜΑΤΙΚΗΣ ΕΡΓΑΣΙΑΣ</h2>
    <p>Ανακοινώνεται ότι ο/η φοιτητής/τρια <strong><?= h($thesis['student_name']) ?></strong>
       θα παρουσιάσει τη διπλωματική του/της εργασία με τίτλο:</p>
    <p style="font-size:1.1rem;font-weight:700;text-align:center;">«<?= h($thesis['title']) ?>»</p>
    <p><strong>Ημερομηνία/Ώρα:</strong> <?= h($thesis['exam_datetime']) ?></p>
    <p><strong>Τρόπος:</strong> <?= $thesis['exam_mode'] === 'online' ? 'Διαδικτυακά' : 'Δια ζώσης' ?></p>
    <?php if ($thesis['exam_mode'] === 'online' && $thesis['exam_link']): ?>
        <p><strong>Σύνδεσμος:</strong> <a href="<?= h($thesis['exam_link']) ?>"><?= h($thesis['exam_link']) ?></a></p>
    <?php elseif ($thesis['exam_location']): ?>
        <p><strong>Αίθουσα:</strong> <?= h($thesis['exam_location']) ?></p>
    <?php endif; ?>
    <p><strong>Επιβλέπων:</strong> <?= h($thesis['supervisor_name']) ?></p>
    <p><strong>Τριμελής Εξεταστική Επιτροπή:</strong> <?= h(implode(', ', $members)) ?></p>
    <p>Η παρουσίαση είναι ανοιχτή στο κοινό.</p>
</div>
</body>
</html>
