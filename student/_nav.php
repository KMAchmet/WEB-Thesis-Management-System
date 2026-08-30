<header class="site-header">
    <img src="../media/logo.svg" alt="Λογότυπο" class="logo">
    <h1>Φοιτητής/τρια — <?= h($_SESSION['full_name'] ?? '') ?></h1>
</header>
<nav class="navbar">
    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">Θέμα Διπλωματικής</a>
    <a href="manage_thesis.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_thesis.php' ? 'active' : '' ?>">Διαχείριση</a>
    <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">Επεξεργασία Προφίλ</a>
    <span class="spacer"></span>
    <a href="../logout.php">Αποσύνδεση</a>
</nav>
