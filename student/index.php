<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$studentId = current_user_id();

$stmt = $pdo->prepare(
    "SELECT t.*, tp.title, tp.short_description, tp.pdf_path, up.full_name AS supervisor_name
     FROM theses t
     JOIN topics tp ON tp.id = t.topic_id
     JOIN professors p ON p.user_id = t.supervisor_id
     JOIN users up ON up.id = p.user_id
     WHERE t.student_id = :sid
     ORDER BY t.created_at DESC LIMIT 1"
);
$stmt->execute([':sid' => $studentId]);
$thesis = $stmt->fetch();

$committee = [];
if ($thesis) {
    $cstmt = $pdo->prepare(
        "SELECT cm.*, u.full_name FROM committee_members cm
         JOIN users u ON u.id = cm.professor_id
         WHERE cm.thesis_id = :tid ORDER BY cm.member_role DESC"
    );
    $cstmt->execute([':tid' => $thesis['id']]);
    $committee = $cstmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Η Διπλωματική μου — Φοιτητής</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/student.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Η Διπλωματική μου Εργασία</h2>

    <?php if (!$thesis): ?>
        <div class="card">
            <p>Δεν έχετε αναλάβει ακόμα κάποιο θέμα διπλωματικής εργασίας.</p>
            <p>Η ανάθεση θέματος γίνεται από Διδάσκοντα. Επικοινωνήστε με τον διδάσκοντα της
               επιλογής σας για να σας αναθέσει ένα θέμα.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <span class="<?= status_class($thesis['status']) ?>"><?= h(status_label($thesis['status'])) ?></span>
            <h3><?= h($thesis['title']) ?></h3>
            <p><?= nl2br(h($thesis['short_description'])) ?></p>
            <?php if ($thesis['pdf_path']): ?>
                <p><img src="../media/pdf-icon.svg" width="18" alt="PDF">
                   <a href="<?= h($thesis['pdf_path']) ?>" target="_blank">Αναλυτική περιγραφή θέματος (PDF)</a></p>
            <?php endif; ?>
            <p><strong>Επιβλέπων:</strong> <?= h($thesis['supervisor_name']) ?></p>
            <?php if ($thesis['assigned_at']): ?>
                <p><strong>Χρόνος από την επίσημη ανάθεση:</strong> <?= h(elapsed_since($thesis['assigned_at'])) ?></p>
            <?php endif; ?>

            <h4>Μέλη Τριμελούς Επιτροπής</h4>
            <table>
                <thead><tr><th>Όνομα</th><th>Ρόλος</th><th>Κατάσταση Πρόσκλησης</th></tr></thead>
                <tbody>
                <?php foreach ($committee as $c): ?>
                    <tr>
                        <td data-label="Όνομα"><?= h($c['full_name']) ?></td>
                        <td data-label="Ρόλος"><?= $c['member_role'] === 'supervisor' ? 'Επιβλέπων' : 'Μέλος' ?></td>
                        <td data-label="Κατάσταση">
                            <?= match($c['invitation_status']) {
                                'accepted' => '✅ Αποδέχθηκε',
                                'pending' => '⏳ Εκκρεμεί',
                                'rejected' => '❌ Απορρίφθηκε',
                                'cancelled' => '➖ Ακυρώθηκε',
                                default => $c['invitation_status'],
                            } ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:16px;">
                <a class="btn btn-primary" href="manage_thesis.php">Διαχείριση Διπλωματικής</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
