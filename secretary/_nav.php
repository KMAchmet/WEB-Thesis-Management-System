<header class="site-header">
    <img src="../media/logo.svg" alt="Λογότυπο" class="logo">
    <h1>Γραμματεία — <?= h($_SESSION['full_name'] ?? '') ?></h1>
</header>
<?php $current = basename($_SERVER['PHP_SELF']); ?>
<nav class="navbar">
    <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Προβολή ΔΕ</a>
    <a href="import_data.php" class="<?= $current === 'import_data.php' ? 'active' : '' ?>">Εισαγωγή Δεδομένων</a>
    <span class="spacer"></span>
    <a href="../logout.php">Αποσύνδεση</a>
</nav>
