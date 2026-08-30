<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login(); // any authenticated role may view, ownership checked below for students

$thesisId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT t.*, tp.title, u.full_name AS student_name, s.am
     FROM theses t
     JOIN topics tp ON tp.id = t.topic_id
     JOIN students s ON s.user_id = t.student_id
     JOIN users u ON u.id = s.user_id
     WHERE t.id = :id"
);
$stmt->execute([':id' => $thesisId]);
$thesis = $stmt->fetch();

if (!$thesis) { die('Δεν βρέθηκε η διπλωματική εργασία.'); }
if (current_role() === 'student' && $thesis['student_id'] != current_user_id()) {
    die('Δεν έχετε πρόσβαση σε αυτό το πρακτικό.');
}

$cstmt = $pdo->prepare(
    "SELECT cm.*, u.full_name FROM committee_members cm
     JOIN users u ON u.id = cm.professor_id
     WHERE cm.thesis_id = :id AND cm.invitation_status = 'accepted'
     ORDER BY cm.member_role DESC"
);
$cstmt->execute([':id' => $thesisId]);
$committee = $cstmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<title>Πρακτικό Εξέτασης — <?= h($thesis['title']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
  body { padding: 30px; }
  .report-box { max-width: 750px; margin: 0 auto; border: 2px solid #2c5f8a; padding: 30px; }
  .report-box h2 { text-align: center; }
  .sig-line { margin-top: 60px; display: flex; justify-content: space-between; }
  .sig-line div { text-align: center; width: 30%; border-top: 1px solid #333; padding-top: 6px; }
</style>
</head>
<body>
<div class="report-box">
    <h2>ΠΡΑΚΤΙΚΟ ΕΞΕΤΑΣΗΣ ΔΙΠΛΩΜΑΤΙΚΗΣ ΕΡΓΑΣΙΑΣ</h2>
    <p><strong>Τμήμα Μηχανικών Η/Υ &amp; Πληροφορικής — Πανεπιστήμιο Πατρών</strong></p>
    <p><strong>Φοιτητής/τρια:</strong> <?= h($thesis['student_name']) ?> (ΑΜ: <?= h($thesis['am']) ?>)</p>
    <p><strong>Τίτλος Διπλωματικής:</strong> <?= h($thesis['title']) ?></p>
    <p><strong>Ημερομηνία Εξέτασης:</strong> <?= h($thesis['exam_datetime']) ?></p>
    <p><strong>ΑΠ Γ.Σ. Ανάθεσης:</strong> <?= h($thesis['ga_number']) ?>/<?= h($thesis['ga_year']) ?></p>

    <h3>Βαθμολογία Τριμελούς Επιτροπής</h3>
    <table>
        <thead><tr><th>Μέλος</th><th>Ρόλος</th><th>Βαθμός</th></tr></thead>
        <tbody>
        <?php foreach ($committee as $c): ?>
            <tr>
                <td data-label="Μέλος"><?= h($c['full_name']) ?></td>
                <td data-label="Ρόλος"><?= $c['member_role'] === 'supervisor' ? 'Επιβλέπων' : 'Μέλος' ?></td>
                <td data-label="Βαθμός"><?= h($c['grade'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="font-size:1.2rem;margin-top:20px;">
        <strong>Τελικός Βαθμός: <?= h($thesis['final_grade'] ?? 'εκκρεμεί') ?></strong>
    </p>

    <div class="sig-line">
        <div>Επιβλέπων/ουσα</div>
        <div>Μέλος Τριμελούς</div>
        <div>Μέλος Τριμελούς</div>
    </div>
</div>
</body>
</html>
