<?php
/**
 * db/seed_users.php
 * -----------------------------------------------------------
 * Run this ONCE from the command line (php seed_users.php) or via
 * browser after 01_schema.sql / 02_procedures.sql / 03_triggers.sql
 * have been imported. It creates the initial 10 students,
 * 5 professors and 1 secretary account, all with the password:
 *      password123
 * hashed properly with PHP's password_hash().
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../includes/db.php';

$defaultPassword = password_hash('password123', PASSWORD_DEFAULT);

$professors = [
    ['prof1@upatras.gr', 'Καθηγητής Νικόλαος Παπαδόπουλος'],
    ['prof2@upatras.gr', 'Καθηγήτρια Ελένη Ιωαννίδου'],
    ['prof3@upatras.gr', 'Καθηγητής Δημήτριος Κωνσταντίνου'],
    ['prof4@upatras.gr', 'Καθηγήτρια Μαρία Αντωνίου'],
    ['prof5@upatras.gr', 'Καθηγητής Γεώργιος Σταυρόπουλος'],
];

$students = [
    ['stud1@upnet.gr', 'Γιάννης Παπαγιάννης', '1071001'],
    ['stud2@upnet.gr', 'Ελένη Δημητρίου', '1071002'],
    ['stud3@upnet.gr', 'Κώστας Βασιλείου', '1071003'],
    ['stud4@upnet.gr', 'Άννα Μιχαηλίδου', '1071004'],
    ['stud5@upnet.gr', 'Νίκος Οικονόμου', '1071005'],
    ['stud6@upnet.gr', 'Σοφία Παπαδοπούλου', '1071006'],
    ['stud7@upnet.gr', 'Θανάσης Γεωργίου', '1071007'],
    ['stud8@upnet.gr', 'Κατερίνα Νικολάου', '1071008'],
    ['stud9@upnet.gr', 'Παναγιώτης Αλεξίου', '1071009'],
    ['stud10@upnet.gr', 'Δέσποινα Ζαχαρίου', '1071010'],
];

try {
    $pdo->beginTransaction();

    foreach ($professors as [$email, $name]) {
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password_hash, role, full_name, email)
             VALUES (:u, :p, 'professor', :n, :e)"
        );
        $stmt->execute([':u' => $email, ':p' => $defaultPassword, ':n' => $name, ':e' => $email]);
        $userId = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO professors (user_id, department) VALUES (:id, 'ΤΜΗΥΠ')")
            ->execute([':id' => $userId]);
    }

    foreach ($students as [$email, $name, $am]) {
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, password_hash, role, full_name, email)
             VALUES (:u, :p, 'student', :n, :e)"
        );
        $stmt->execute([':u' => $email, ':p' => $defaultPassword, ':n' => $name, ':e' => $email]);
        $userId = $pdo->lastInsertId();

        $pdo->prepare(
            "INSERT INTO students (user_id, am, address, mobile_phone, home_phone)
             VALUES (:id, :am, 'Πάτρα, Ελλάδα', '6900000000', '2610000000')"
        )->execute([':id' => $userId, ':am' => $am]);
    }

    $pdo->prepare(
        "INSERT INTO users (username, password_hash, role, full_name, email)
         VALUES ('secretary@upatras.gr', :p, 'secretary', 'Γραμματεία ΤΜΗΥΠ', 'secretary@upatras.gr')"
    )->execute([':p' => $defaultPassword]);

    $pdo->commit();
    echo "Seed users created successfully. Default password for everyone: password123\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error seeding users: " . $e->getMessage() . "\n";
}
