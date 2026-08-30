USE thesis_system;

DELIMITER $$

-- ------------------------------------------------------------
-- sp_assign_topic: Αρχική ανάθεση θέματος σε φοιτητή από Διδάσκοντα
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_assign_topic $$
CREATE PROCEDURE sp_assign_topic (
    IN p_topic_id INT,
    IN p_student_id INT
)
BEGIN
    DECLARE v_professor_id INT;
    DECLARE v_thesis_id INT;

    SELECT professor_id INTO v_professor_id FROM topics WHERE id = p_topic_id AND is_taken = 0;

    IF v_professor_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Topic not found or already taken';
    END IF;

    START TRANSACTION;
        UPDATE topics SET is_taken = 1 WHERE id = p_topic_id;

        INSERT INTO theses (topic_id, student_id, supervisor_id, status)
        VALUES (p_topic_id, p_student_id, v_professor_id, 'under_assignment');

        SET v_thesis_id = LAST_INSERT_ID();

        INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status, responded_at)
        VALUES (v_thesis_id, v_professor_id, 'supervisor', 'accepted', NOW());

        INSERT INTO thesis_status_history (thesis_id, old_status, new_status, note)
        VALUES (v_thesis_id, NULL, 'under_assignment', 'Αρχική ανάθεση από διδάσκοντα');
    COMMIT;

    SELECT v_thesis_id AS thesis_id;
END $$

-- ------------------------------------------------------------
-- sp_invite_committee_member: Φοιτητής προσκαλεί μέλος τριμελούς
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_invite_committee_member $$
CREATE PROCEDURE sp_invite_committee_member (
    IN p_thesis_id INT,
    IN p_professor_id INT
)
BEGIN
    INSERT INTO committee_members (thesis_id, professor_id, member_role, invitation_status)
    VALUES (p_thesis_id, p_professor_id, 'member', 'pending');
END $$

-- ------------------------------------------------------------
-- sp_cancel_topic_assignment: Ακύρωση ανάθεσης (κατάσταση "Υπό Ανάθεση")
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_cancel_topic_assignment $$
CREATE PROCEDURE sp_cancel_topic_assignment (
    IN p_thesis_id INT
)
BEGIN
    DECLARE v_topic_id INT;

    SELECT topic_id INTO v_topic_id FROM theses WHERE id = p_thesis_id;

    START TRANSACTION;
        DELETE FROM committee_members WHERE thesis_id = p_thesis_id;

        UPDATE theses SET status = 'cancelled', cancel_reason = 'Ακύρωση πριν την οριστικοποίηση'
        WHERE id = p_thesis_id;

        UPDATE topics SET is_taken = 0 WHERE id = v_topic_id;

        INSERT INTO thesis_status_history (thesis_id, old_status, new_status, note)
        VALUES (p_thesis_id, 'under_assignment', 'cancelled', 'Ακύρωση ανάθεσης από διδάσκοντα');
    COMMIT;
END $$

-- ------------------------------------------------------------
-- sp_professor_statistics: στατιστικά διδάσκοντα
-- ------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_professor_statistics $$
CREATE PROCEDURE sp_professor_statistics (
    IN p_professor_id INT
)
BEGIN
    -- Ως επιβλέπων
    SELECT
        'supervisor' AS role_type,
        COUNT(*) AS total_count,
        AVG(final_grade) AS avg_grade,
        AVG(TIMESTAMPDIFF(DAY, assigned_at, updated_completed.completed_at)) AS avg_days
    FROM theses t
    LEFT JOIN (
        SELECT thesis_id, MAX(changed_at) AS completed_at
        FROM thesis_status_history WHERE new_status = 'completed'
        GROUP BY thesis_id
    ) updated_completed ON updated_completed.thesis_id = t.id
    WHERE t.supervisor_id = p_professor_id

    UNION ALL

    -- Ως μέλος τριμελούς
    SELECT
        'member' AS role_type,
        COUNT(*) AS total_count,
        AVG(t.final_grade) AS avg_grade,
        AVG(TIMESTAMPDIFF(DAY, t.assigned_at, updated_completed.completed_at)) AS avg_days
    FROM committee_members cm
    JOIN theses t ON t.id = cm.thesis_id
    LEFT JOIN (
        SELECT thesis_id, MAX(changed_at) AS completed_at
        FROM thesis_status_history WHERE new_status = 'completed'
        GROUP BY thesis_id
    ) updated_completed ON updated_completed.thesis_id = t.id
    WHERE cm.professor_id = p_professor_id AND cm.member_role = 'member';
END $$

DELIMITER ;
