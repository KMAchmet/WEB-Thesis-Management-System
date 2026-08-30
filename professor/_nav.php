<header class="site-header">
    <img src="../media/logo.svg" alt="Λογότυπο" class="logo">
    <h1>Διδάσκων — <?= h($_SESSION['full_name'] ?? '') ?></h1>
</header>
<?php $current = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar">
    <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Αρχική</a>
    <a href="topics.php" class="<?= $current === 'topics.php' ? 'active' : '' ?>">Θέματα</a>
    <a href="assign_topic.php" class="<?= $current === 'assign_topic.php' ? 'active' : '' ?>">Ανάθεση σε Φοιτητή</a>
    <a href="thesis_list.php" class="<?= $current === 'thesis_list.php' ? 'active' : '' ?>">Λίστα Διπλωματικών</a>
    <a href="invitations.php" class="<?= $current === 'invitations.php' ? 'active' : '' ?>">Προσκλήσεις Τριμελούς</a>
    <a href="statistics.php" class="<?= $current === 'statistics.php' ? 'active' : '' ?>">Στατιστικά</a>
    <span class="spacer"></span>
    <a href="../logout.php">Αποσύνδεση</a>
</nav>
