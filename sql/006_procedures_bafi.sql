-- 006_procedures_bafi.sql
-- Additive / Idempotent: BAFI RAW view + export procedure
-- מתבסס על מבנה קיים: placements ללא 'status'. Active = end_date IS NULL OR end_date >= CURDATE().

SET NAMES utf8mb4;

-- View: v_bafi_employee_raw
-- מאחד נתוני עובד + השיבוץ הפעיל (הכי מאוחר) + פרטי המעסיק של השיבוץ
-- לא משתמש במשתנים כדי להימנע מ- #1351
CREATE OR REPLACE VIEW v_bafi_employee_raw AS
SELECT
  e.id                         AS employee_id,

  -- עובֵד (שדות נפוצים במבנה שלך)
  e.id_type_code               AS emp_id_type_code,
  e.id_number                  AS emp_id_number,
  e.passport_number            AS emp_passport_number,
  e.first_name                 AS emp_first_name,
  e.last_name                  AS emp_last_name,
  e.gender_code                AS emp_gender_code,
  e.marital_status_code        AS emp_marital_status_code,
  COALESCE(e.date_of_birth, e.birth_date) AS emp_birth_date,  -- קיימים שני שמות אצלך; ניקח עדיפות ל-date_of_birth אם יש
  e.country_of_citizenship     AS emp_country_of_citizenship,
  e.city_code                  AS emp_city_code,
  e.street_code                AS emp_street_code,
  COALESCE(e.house_number, e.house_no) AS emp_house_number,
  e.apartment                  AS emp_apartment,
  COALESCE(e.postal_code, e.zipcode) AS emp_postal_code,
  e.phone                      AS emp_phone,
  e.phone_alt                  AS emp_phone_alt,
  e.email                      AS emp_email,

  -- שיבוץ פעיל (הכי מאוחר לפי start_date)
  p.id                         AS placement_id,
  p.employer_id                AS employer_id,
  p.start_date                 AS placement_start_date,
  p.end_date                   AS placement_end_date,
  p.end_reason_code            AS placement_end_reason_code,

  -- פרטי מעסיק
  r.id_type_code               AS er_id_type_code,
  r.id_number                  AS er_id_number,
  r.first_name                 AS er_first_name,
  r.last_name                  AS er_last_name,
  r.phone                      AS er_phone,
  r.phone_alt                  AS er_phone_alt,
  r.email                      AS er_email,
  r.birth_date                 AS er_birth_date,
  r.city_code                  AS er_city_code,
  r.street_code                AS er_street_code,
  COALESCE(r.house_number, r.house_no) AS er_house_number,
  r.apartment                  AS er_apartment,
  r.zipcode                    AS er_postal_code

FROM employees e
-- בחירת השיבוץ הפעיל העדכני לכל עובד
LEFT JOIN (
    SELECT employee_id, MAX(start_date) AS max_start_date
    FROM placements
    WHERE end_date IS NULL OR end_date >= CURDATE()
    GROUP BY employee_id
) pm ON pm.employee_id = e.id
LEFT JOIN placements p
    ON p.employee_id = e.id
   AND p.start_date = pm.max_start_date
LEFT JOIN employers r
    ON r.id = p.employer_id
;

-- Procedure: sp_bafi_export_raw
DROP PROCEDURE IF EXISTS sp_bafi_export_raw;
DELIMITER $$
CREATE PROCEDURE sp_bafi_export_raw(IN p_employee_id BIGINT UNSIGNED)
BEGIN
  SELECT *
  FROM v_bafi_employee_raw
  WHERE employee_id = p_employee_id;
END$$
DELIMITER ;
