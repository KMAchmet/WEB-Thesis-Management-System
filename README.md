# Σύστημα Υποστήριξης Διπλωματικών Εργασιών

Σκελετός εφαρμογής σε **PHP, MySQL, AJAX & Chart.js**.

> ⚠️ **Προσοχή:** Απαιτείται προσθήκη μέτρων ασφαλείας (CSRF, δικαιώματα) πριν την τελική παράδοση.

---

## 📁 Δομή

- `index.html`, `login.php`, `logout.php`: Αρχική σελίδα & σύνδεση
- `announcements.php`: Δημόσιο feed (`JSON`/`XML`)
- `student/`, `professor/`, `secretary/`: Dashboards ανά ρόλο
- `includes/`: Σύνδεση βάσης (`db.php`) & auth
- `db/`: SQL scripts & seed δεδομένων (`seed_users.php`, κωδικός: `password123`)

---

## 🚀 Εγκατάσταση

1. **Βάση:** Εκτελέστε με τη σειρά: `01_schema.sql` ➔ `02_procedures.sql` ➔ `03_triggers.sql`
2. **Ρύθμιση:** Ενημερώστε το `includes/db.php`
3. **Data:** Εκτελέστε `php db/seed_users.php` και μετά το `04_seed_data.sql`
4. **Run:** Εκκινήστε τον server (`php -S localhost:8000`)

---

## 📌 Εκκρεμότητες

- [ ] CSRF tokens & έλεγχος δικαιωμάτων
- [ ] Export σε PDF & WYSIWYG editor
- [ ] ER diagram & screenshots
