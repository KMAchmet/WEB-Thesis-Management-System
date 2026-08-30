<?php
/**
 * announcements.php
 * Public, unauthenticated endpoint listing thesis presentation
 * announcements ("Υπό Εξέταση" theses with a scheduled exam date).
 *
 * Params:
 *   format = json|xml   (default json)
 *   from   = YYYY-MM-DD (default: today)
 *   to     = YYYY-MM-DD (default: today + 90 days)
 */
require_once __DIR__ . '/includes/db.php';

$format = strtolower($_GET['format'] ?? 'json');
$from   = $_GET['from'] ?? date('Y-m-d');
$to     = $_GET['to']   ?? date('Y-m-d', strtotime('+90 days'));

$stmt = $pdo->prepare(
    "SELECT t.id, tp.title, u.full_name AS student_name,
            t.exam_datetime, t.exam_mode, t.exam_location, t.exam_link,
            up.full_name AS supervisor_name
     FROM theses t
     JOIN topics tp ON tp.id = t.topic_id
     JOIN students s ON s.user_id = t.student_id
     JOIN users u ON u.id = s.user_id
     JOIN professors p ON p.user_id = t.supervisor_id
     JOIN users up ON up.id = p.user_id
     WHERE t.status = 'under_examination'
       AND t.exam_datetime IS NOT NULL
       AND DATE(t.exam_datetime) BETWEEN :from AND :to
     ORDER BY t.exam_datetime ASC"
);
$stmt->execute([':from' => $from, ':to' => $to]);
$rows = $stmt->fetchAll();

if ($format === 'xml') {
    header('Content-Type: application/xml; charset=utf-8');
    $xml = new SimpleXMLElement('<announcements/>');
    $xml->addAttribute('from', $from);
    $xml->addAttribute('to', $to);
    foreach ($rows as $r) {
        $item = $xml->addChild('announcement');
        $item->addChild('thesis_id', $r['id']);
        $item->addChild('title', htmlspecialchars($r['title']));
        $item->addChild('student', htmlspecialchars($r['student_name']));
        $item->addChild('supervisor', htmlspecialchars($r['supervisor_name']));
        $item->addChild('exam_datetime', $r['exam_datetime']);
        $item->addChild('exam_mode', $r['exam_mode']);
        $item->addChild('location', htmlspecialchars($r['exam_location'] ?? ''));
        $item->addChild('link', htmlspecialchars($r['exam_link'] ?? ''));
    }
    echo $xml->asXML();
    exit;
}

// default: JSON feed
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'from' => $from,
    'to' => $to,
    'count' => count($rows),
    'announcements' => $rows,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
