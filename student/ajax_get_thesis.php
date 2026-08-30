<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('student');

$thesisId = (int)($_GET['thesis_id'] ?? 0);

// make sure this thesis belongs to the logged-in student
$check = $pdo->prepare("SELECT id, status FROM theses WHERE id = :id AND student_id = :sid");
$check->execute([':id' => $thesisId, ':sid' => current_user_id()]);
$thesis = $check->fetch();

if (!$thesis) {
    json_response(['error' => 'not_found'], 404);
}

$stmt = $pdo->prepare(
    "SELECT cm.member_role, cm.invitation_status, u.full_name
     FROM committee_members cm JOIN users u ON u.id = cm.professor_id
     WHERE cm.thesis_id = :id ORDER BY cm.member_role DESC"
);
$stmt->execute([':id' => $thesisId]);

json_response([
    'status' => $thesis['status'],
    'committee' => $stmt->fetchAll(),
]);
