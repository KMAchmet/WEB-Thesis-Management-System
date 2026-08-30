<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');

$profId = current_user_id();

$stmt = $pdo->prepare("CALL sp_professor_statistics(:p)");
$stmt->execute([':p' => $profId]);
$rows = $stmt->fetchAll();

$result = [
    'supervisor' => ['total_count' => 0, 'avg_grade' => 0, 'avg_days' => 0],
    'member'     => ['total_count' => 0, 'avg_grade' => 0, 'avg_days' => 0],
];

foreach ($rows as $r) {
    $key = $r['role_type'];
    $result[$key] = [
        'total_count' => (int)$r['total_count'],
        'avg_grade'   => $r['avg_grade'] !== null ? round((float)$r['avg_grade'], 2) : 0,
        'avg_days'    => $r['avg_days'] !== null ? round((float)$r['avg_days'], 1) : 0,
    ];
}

json_response($result);
