<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();

$pendingInvites = $pdo->prepare(
    "SELECT COUNT(*) FROM committee_members WHERE professor_id = :p AND invitation_status = 'pending'"
);
$pendingInvites->execute([':p' => $profId]);
$pendingCount = $pendingInvites->fetchColumn();

$activeTopics = $pdo->prepare("SELECT COUNT(*) FROM topics WHERE professor_id = :p AND is_taken = 0");
$activeTopics->execute([':p' => $profId]);
$freeTopicCount = $activeTopics->fetchColumn();

$myTheses = $pdo->prepare(
    "SELECT COUNT(*) FROM theses WHERE supervisor_id = :p
     UNION ALL
     SELECT COUNT(*) FROM committee_members WHERE professor_id = :p2 AND member_role = 'member' AND invitation_status='accepted'"
);
$myTheses->execute([':p' => $profId, ':p2' => $profId]);
$counts = $myTheses->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Αρχική — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Καλώς ήρθατε, <?= h($_SESSION['full_name']) ?></h2>

    <div class="dash-grid">
        <a class="dash-card" href="topics.php">
            <h3>Θέματα προς Ανάθεση</h3>
            <p class="dash-number"><?= (int)$freeTopicCount ?></p>
            <p>Ελεύθερα θέματα</p>
        </a>
        <a class="dash-card" href="invitations.php">
            <h3>Προσκλήσεις Τριμελούς</h3>
            <p class="dash-number"><?= (int)$pendingCount ?></p>
            <p>Εκκρεμείς προσκλήσεις</p>
        </a>
        <a class="dash-card" href="thesis_list.php">
            <h3>Διπλωματικές</h3>
            <p class="dash-number"><?= (int)($counts[0] + $counts[1]) ?></p>
            <p>Ως επιβλέπων ή μέλος</p>
        </a>
        <a class="dash-card" href="statistics.php">
            <h3>Στατιστικά</h3>
            <p>Δείτε γραφήματα προόδου</p>
        </a>
    </div>
</div>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
