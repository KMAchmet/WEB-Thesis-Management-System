<?php
/**
 * includes/functions.php
 * Small helper functions reused across student / professor / secretary pages.
 */

/** Safe HTML output */
function h($str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/** Greek labels for thesis statuses */
function status_label(string $status): string
{
    $labels = [
        'under_assignment'   => 'Υπό Ανάθεση',
        'active'             => 'Ενεργή',
        'under_examination'  => 'Υπό Εξέταση',
        'completed'          => 'Περατωμένη',
        'cancelled'          => 'Ακυρωμένη',
    ];
    return $labels[$status] ?? $status;
}

/** CSS class helper for status badges */
function status_class(string $status): string
{
    $classes = [
        'under_assignment'  => 'badge badge-pending',
        'active'            => 'badge badge-active',
        'under_examination' => 'badge badge-exam',
        'completed'         => 'badge badge-completed',
        'cancelled'         => 'badge badge-cancelled',
    ];
    return $classes[$status] ?? 'badge';
}

/** Human friendly elapsed time, e.g. "3 μήνες, 12 ημέρες" */
function elapsed_since(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $start = new DateTime($datetime);
    $now = new DateTime();
    $diff = $start->diff($now);

    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' έτη';
    if ($diff->m > 0) $parts[] = $diff->m . ' μήνες';
    if ($diff->y == 0 && $diff->d > 0) $parts[] = $diff->d . ' ημέρες';

    return $parts ? implode(', ', $parts) : 'σήμερα';
}

/** JSON response helper for AJAX endpoints */
function json_response($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
