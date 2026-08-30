<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $studentId = (int)($_POST['student_id'] ?? 0);

    if ($topicId && $studentId) {
        try {
            $pdo->prepare("CALL sp_assign_topic(:t, :s)")->execute([':t' => $topicId, ':s' => $studentId]);
            $message = 'Το θέμα ανατέθηκε προσωρινά στο φοιτητή. Αναμένεται η αποδοχή της τριμελούς.';
        } catch (PDOException $e) {
            $message = 'Σφάλμα: ' . $e->getMessage();
        }
    }
}

$topicsStmt = $pdo->prepare("SELECT id, title FROM topics WHERE professor_id = :p AND is_taken = 0");
$topicsStmt->execute([':p' => $profId]);
$freeTopics = $topicsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ανάθεση Θέματος — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Αρχική Ανάθεση Θέματος σε Φοιτητή</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>

    <div class="card">
        <form method="post" id="assign-form">
            <div class="form-group">
                <label for="topic_id">Ελεύθερο Θέμα</label>
                <select id="topic_id" name="topic_id" required>
                    <option value="">-- Επιλέξτε θέμα --</option>
                    <?php foreach ($freeTopics as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= h($t['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="student_search">Αναζήτηση Φοιτητή (ΑΜ ή Ονοματεπώνυμο)</label>
                <input type="text" id="student_search" placeholder="π.χ. 1071001 ή Παπαγιάννης" autocomplete="off">
                <div id="student_results" class="search-results"></div>
                <input type="hidden" id="student_id" name="student_id">
                <p id="student_selected" style="font-weight:600;color:var(--color-primary);"></p>
            </div>
            <button type="submit" class="btn btn-primary">Ανάθεση Θέματος</button>
        </form>
    </div>
</div>

<script>
const searchInput = document.getElementById('student_search');
const resultsBox = document.getElementById('student_results');
let debounceTimer;

searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) { resultsBox.innerHTML = ''; return; }
    debounceTimer = setTimeout(() => {
        fetch('ajax_search_students.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                resultsBox.innerHTML = '';
                data.forEach(s => {
                    const div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.textContent = s.full_name + ' (ΑΜ: ' + s.am + ')';
                    div.onclick = () => {
                        document.getElementById('student_id').value = s.id;
                        document.getElementById('student_selected').textContent = 'Επιλέχθηκε: ' + div.textContent;
                        resultsBox.innerHTML = '';
                        searchInput.value = '';
                    };
                    resultsBox.appendChild(div);
                });
            });
    }, 300);
});
</script>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
