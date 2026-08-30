USE thesis_system;
-- Run this AFTER seed_users.php has created users 1-16 (5 professors id 1-5, 10 students id 6-15, secretary id 16)
-- Adjust the IDs below if your auto-increment values differ.

-- Topics created by professors (some free, some taken)
INSERT INTO topics (professor_id, title, short_description, is_taken) VALUES
(1, 'Σύστημα Συστάσεων με Μηχανική Μάθηση', 'Ανάπτυξη recommender system με χρήση collaborative filtering.', 1),
(1, 'Ανάλυση Δικτύων Κοινωνικής Δικτύωσης', 'Μελέτη γράφων και εξαγωγή κοινοτήτων σε social graphs.', 0),
(2, 'Εφαρμογή IoT για Έξυπνο Σπίτι', 'Σχεδίαση συστήματος αισθητήρων και web dashboard.', 1),
(2, 'Ανάπτυξη Progressive Web App', 'PWA με offline λειτουργικότητα και push notifications.', 0),
(3, 'Ασφάλεια Web Εφαρμογών', 'Μελέτη ευπαθειών OWASP Top 10 και αντίμετρα.', 1),
(3, 'Blockchain για Ψηφιακά Πιστοποιητικά', 'Υλοποίηση συστήματος έκδοσης πιστοποιητικών σε blockchain.', 0),
(4, 'Επεξεργασία Φυσικής Γλώσσας σε Ελληνικά Κείμενα', 'NLP pipeline για ανάλυση συναισθήματος.', 1),
(4, 'Σύστημα Διαχείρισης Βιβλιοθήκης', 'Web εφαρμογή δανεισμού βιβλίων με QR codes.', 0),
(5, 'Ανάπτυξη Mobile Εφαρμογής Υγείας', 'Cross-platform εφαρμογή παρακολούθησης δραστηριότητας.', 1),
(5, 'Οπτικοποίηση Μεγάλων Δεδομένων', 'Interactive dashboards με D3.js / Chart.js.', 0);

-- Theses covering all statuses
-- 1) under_assignment: student picked topic, waiting on committee invitations
INSERT INTO theses (topic_id, student_id, supervisor_id, status) VALUES
(2, 6, 1, 'under_assignment');
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at) VALUES
(1, 1, 'supervisor', 'accepted', NOW());
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status) VALUES
(1, 2, 'member', 'pending'),
(1, 3, 'member', 'pending');
UPDATE topics SET is_taken = 1 WHERE id = 2;

-- 2) active: full committee accepted, working on the thesis
INSERT INTO theses (topic_id, student_id, supervisor_id, status, assigned_at, ga_number, ga_year) VALUES
(1, 7, 1, 'active', '2025-02-10 00:00:00', '145', 2025);
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at) VALUES
(2, 1, 'supervisor', 'accepted', '2025-02-01'),
(2, 2, 'member', 'accepted', '2025-02-05'),
(2, 3, 'member', 'accepted', '2025-02-08');

-- 3) under_examination: draft submitted, exam scheduled, grading in progress
INSERT INTO theses (topic_id, student_id, supervisor_id, status, assigned_at, ga_number, ga_year,
                     draft_file_path, exam_datetime, exam_mode, exam_location) VALUES
(3, 8, 2, 'under_examination', '2024-09-01 00:00:00', '98', 2024,
 '/uploads/drafts/thesis3_draft.pdf', '2026-09-15 11:00:00', 'in_person', 'Αίθουσα Β1');
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at, grade) VALUES
(3, 2, 'supervisor', 'accepted', '2024-08-20', NULL),
(3, 4, 'member', 'accepted', '2024-08-25', 9.0),
(3, 5, 'member', 'accepted', '2024-08-28', NULL);

-- 4) completed: fully graded, final grade computed
INSERT INTO theses (topic_id, student_id, supervisor_id, status, assigned_at, ga_number, ga_year,
                     draft_file_path, exam_datetime, exam_mode, exam_location, nemertes_link, final_grade) VALUES
(5, 9, 3, 'completed', '2024-03-01 00:00:00', '55', 2024,
 '/uploads/drafts/thesis4_draft.pdf', '2024-10-10 10:00:00', 'in_person', 'Αίθουσα Α2',
 'https://nemertes.upatras.gr/handle/example4', 8.67);
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at, grade) VALUES
(4, 3, 'supervisor', 'accepted', '2024-02-15', 9.0),
(4, 1, 'member', 'accepted', '2024-02-18', 8.5),
(4, 4, 'member', 'accepted', '2024-02-20', 8.5);

-- 5) cancelled: cancelled by professor after 2 years, or by secretary
INSERT INTO theses (topic_id, student_id, supervisor_id, status, assigned_at, cancel_ga_number, cancel_ga_year, cancel_reason) VALUES
(7, 10, 4, 'cancelled', '2022-01-10 00:00:00', '12', 2024, 'από Διδάσκοντα');
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at) VALUES
(5, 4, 'supervisor', 'accepted', '2022-01-01'),
(5, 5, 'member', 'accepted', '2022-01-05'),
(5, 2, 'member', 'accepted', '2022-01-08');

-- another under_assignment example for student 11
INSERT INTO theses (topic_id, student_id, supervisor_id, status) VALUES
(9, 11, 5, 'under_assignment');
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at) VALUES
(6, 5, 'supervisor', 'accepted', NOW());
INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status) VALUES
(6, 1, 'member', 'pending');
UPDATE topics SET is_taken = 1 WHERE id = 9;

-- A note example
INSERT INTO notes (thesis_id, professor_id, note_text) VALUES
(2, 1, 'Ο φοιτητής προχωράει καλά, έχει ολοκληρώσει το θεωρητικό υπόβαθρο.');

-- Sample materials for the under_examination thesis
INSERT INTO materials (thesis_id, material_type, label, path_or_url) VALUES
(3, 'link', 'Demo Video στο YouTube', 'https://youtube.com/example-demo'),
(3, 'link', 'Πηγαίος κώδικας στο Google Drive', 'https://drive.google.com/example-code');
