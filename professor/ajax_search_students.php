<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    json_response([]);
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.full_name, s.am FROM students s
     JOIN users u ON u.id = s.user_id
     WHERE s.am LIKE :q OR u.full_name LIKE :q2
     LIMIT 10"
);
$like = '%' . $q . '%';
$stmt->execute([':q' => $like, ':q2' => $like]);
json_response($stmt->fetchAll());
