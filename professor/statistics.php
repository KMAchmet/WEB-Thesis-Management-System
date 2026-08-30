<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('professor');
?>
<!DOCTYPE html>
<html lang="el">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Στατιστικά — Διδάσκων</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="css/professor.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="container">
    <h2>Στατιστικά Διπλωματικών</h2>
    <div class="card"><canvas id="chartCount" height="100"></canvas></div>
    <div class="card"><canvas id="chartAvgGrade" height="100"></canvas></div>
    <div class="card"><canvas id="chartAvgDays" height="100"></canvas></div>
</div>

<script>
fetch('ajax_statistics.php')
    .then(r => r.json())
    .then(data => {
        new Chart(document.getElementById('chartCount'), {
            type: 'bar',
            data: {
                labels: ['Ως Επιβλέπων', 'Ως Μέλος Τριμελούς'],
                datasets: [{ label: 'Σύνολο Διπλωματικών', data: [data.supervisor.total_count, data.member.total_count],
                             backgroundColor: ['#2c5f8a', '#5aa9a3'] }]
            },
            options: { plugins: { title: { display: true, text: 'Συνολικό Πλήθος Διπλωματικών' } } }
        });
        new Chart(document.getElementById('chartAvgGrade'), {
            type: 'bar',
            data: {
                labels: ['Ως Επιβλέπων', 'Ως Μέλος Τριμελούς'],
                datasets: [{ label: 'Μέσος Βαθμός', data: [data.supervisor.avg_grade, data.member.avg_grade],
                             backgroundColor: ['#3f9142', '#e0a63e'] }]
            },
            options: { plugins: { title: { display: true, text: 'Μέσος Βαθμός Περατωμένων Διπλωματικών' } } }
        });
        new Chart(document.getElementById('chartAvgDays'), {
            type: 'bar',
            data: {
                labels: ['Ως Επιβλέπων', 'Ως Μέλος Τριμελούς'],
                datasets: [{ label: 'Μέσος Χρόνος Περάτωσης (ημέρες)', data: [data.supervisor.avg_days, data.member.avg_days],
                             backgroundColor: ['#4b4b8f', '#c0392b'] }]
            },
            options: { plugins: { title: { display: true, text: 'Μέσος Χρόνος Περάτωσης' } } }
        });
    });
</script>
<footer class="site-footer"><p>&copy; 2025 ΤΜΗΥΠ — Πανεπιστήμιο Πατρών</p></footer>
</body>
</html>
