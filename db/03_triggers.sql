USE thesis_system;

DELIMITER $$

-- ------------------------------------------------------------
-- trg_thesis_status_change: κάθε αλλαγή κατάστασης ΔΕ καταγράφεται
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_thesis_status_change $$
CREATE TRIGGER trg_thesis_status_change
AFTER UPDATE ON theses
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO thesis_status_history (thesis_id, old_status, new_status)
        VALUES (NEW.id, OLD.status, NEW.status);
    END IF;
END $$

-- ------------------------------------------------------------
-- trg_committee_accept: όταν αποδεχθούν 2 μέλη τριμελούς (member),
-- η ΔΕ γίνεται "active" και ακυρώνονται τυχόν εκκρεμείς προσκλήσεις
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_committee_accept $$
CREATE TRIGGER trg_committee_accept
AFTER UPDATE ON committee_members
FOR EACH ROW
BEGIN
    DECLARE v_accepted_members INT;

    IF NEW.invitation_status = 'accepted' AND OLD.invitation_status <> 'accepted'
       AND NEW.member_role = 'member' THEN

        SELECT COUNT(*) INTO v_accepted_members
        FROM committee_members
        WHERE thesis_id = NEW.thesis_id
          AND member_role = 'member'
          AND invitation_status = 'accepted';

        IF v_accepted_members >= 2 THEN
            UPDATE theses
            SET status = 'active', assigned_at = NOW()
            WHERE id = NEW.thesis_id AND status = 'under_assignment';

            UPDATE committee_members
            SET invitation_status = 'cancelled'
            WHERE thesis_id = NEW.thesis_id
              AND member_role = 'member'
              AND invitation_status = 'pending';
        END IF;
    END IF;
END $$

-- ------------------------------------------------------------
-- trg_all_grades_registered: όταν έχουν καταχωρηθεί όλοι οι βαθμοί
-- (3 μέλη επιτροπής), υπολογίζεται αυτόματα ο τελικός βαθμός (μ.ο.)
-- ------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_all_grades_registered $$
CREATE TRIGGER trg_all_grades_registered
AFTER UPDATE ON committee_members
FOR EACH ROW
BEGIN
    DECLARE v_total_members INT;
    DECLARE v_graded_members INT;
    DECLARE v_avg_grade DECIMAL(4,2);

    IF NEW.grade IS NOT NULL AND (OLD.grade IS NULL OR OLD.grade <> NEW.grade) THEN
        SELECT COUNT(*) INTO v_total_members
        FROM committee_members
        WHERE thesis_id = NEW.thesis_id AND invitation_status IN ('accepted');

        SELECT COUNT(*) INTO v_graded_members
        FROM committee_members
        WHERE thesis_id = NEW.thesis_id AND invitation_status = 'accepted' AND grade IS NOT NULL;

        IF v_total_members = v_graded_members AND v_total_members >= 3 THEN
            SELECT AVG(grade) INTO v_avg_grade
            FROM committee_members
            WHERE thesis_id = NEW.thesis_id AND invitation_status = 'accepted';

            UPDATE theses SET final_grade = v_avg_grade WHERE id = NEW.thesis_id;
        END IF;
    END IF;
END $$

DELIMITER ;
