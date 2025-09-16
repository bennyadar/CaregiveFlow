<?php
require_once __DIR__ . '/../models/Employer.php';
require_once __DIR__ . '/../models/CodeTables.php';
require_once __DIR__ . '/../models/Placement.php';

class EmployersController {
    public static function index(PDO $pdo) {
        require_login();
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20; $offset = ($page-1)*$limit;
        $m = new Employer($pdo);
        $rows = $m->all($q, $limit, $offset);
        $total = $m->count($q);
        require __DIR__ . '/../../views/employers/index.php';
    }
    public static function create(PDO $pdo) {
        require_login();
        $codes = new CodeTables($pdo);
        if (is_post()) {
            $m = new Employer($pdo);
            if (empty($_POST['id_type_code']) || empty($_POST['id_number']) || empty($_POST['first_name']) || empty($_POST['last_name'])) {
                flash('אנא מלא/י שדות חובה: סוג ת"ז + מספר ת"ז + שם פרטי + שם משפחה', 'danger');
            } else {
                $id = $m->create($_POST);
                // >>> passports quick-add on employer create
                $rp_num = trim((string)($_POST['rp_passport_number'] ?? ''));
                if ($rp_num !== '') {
                    $rp_country = $_POST['rp_issuing_country_code'] ?? null;
                    $rp_issue   = $_POST['rp_issue_date'] ?? null;
                    $rp_expiry  = $_POST['rp_expiry_date'] ?? null;
                    $rp_primary = !empty($_POST['rp_is_primary']) ? 1 : 0;

                    try {
                        $pdo->beginTransaction();
                        if ($rp_primary) {
                            $pdo->prepare("UPDATE employer_passports SET is_primary = 0 WHERE employer_id = ?")->execute([$id]);
                        }
                        $stmt = $pdo->prepare("
                            INSERT INTO employer_passports
                                (employer_id, passport_number, issuing_country_code, issue_date, expiry_date, is_primary, notes)
                            VALUES (?,?,?,?,?,?,?)
                        ");
                        $stmt->execute([$id, $rp_num, $rp_country, $rp_issue, $rp_expiry, $rp_primary, 'created via employer form']);
                        $pdo->commit();
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) { $pdo->rollBack(); }
                        if (($e->errorInfo[1] ?? null) == 1062) {
                            flash('שימו לב: דרכון זה כבר קיים למעסיק. ההוספה דולגה.', 'warning');
                        } else {
                            throw $e;
                        }
                    }
                }
                // <<< passports quick-add
                flash('המעסיק נשמר בהצלחה!');
                redirect('employers/edit', ['id' => $id]);
            }
        }
        $item = [];
        $id_types  = $codes->employer_id_types();
        $cities    = $codes->cities();
        $genders   = $codes->genders();      // <- חדש
        $countries = $codes->countries();    // <- חדש
        $streets   = []; // אם city נבחר – טען דרך $codes->streetsByCity($city_code)
        require __DIR__ . '/../../views/employers/form.php';
    }
    public static function edit(PDO $pdo) {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new Employer($pdo);
        $item = $m->find($id);
        if (!$item) { flash('מעסיק לא נמצא.', 'danger'); redirect('employers/index'); }
        $codes = new CodeTables($pdo);
        if (is_post()) {
            $m->update($id, $_POST);
            // >>> passports quick-add on employer update
            $rp_num = trim((string)($_POST['rp_passport_number'] ?? ''));
            if ($rp_num !== '') {
                $rp_country = $_POST['rp_issuing_country_code'] ?? null;
                $rp_issue   = $_POST['rp_issue_date'] ?? null;
                $rp_expiry  = $_POST['rp_expiry_date'] ?? null;
                $rp_primary = !empty($_POST['rp_is_primary']) ? 1 : 0;

                try {
                    $pdo->beginTransaction();
                    if ($rp_primary) {
                        $pdo->prepare("UPDATE employer_passports SET is_primary = 0 WHERE employer_id = ?")->execute([$id]);
                    }
                    $stmt = $pdo->prepare("
                        INSERT INTO employer_passports
                            (employer_id, passport_number, issuing_country_code, issue_date, expiry_date, is_primary, notes)
                        VALUES (?,?,?,?,?,?,?)
                    ");
                    $stmt->execute([$id, $rp_num, $rp_country, $rp_issue, $rp_expiry, $rp_primary, 'added via employer edit form']);
                    $pdo->commit();
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    if (($e->errorInfo[1] ?? null) == 1062) {
                        flash('שימו לב: דרכון זה כבר קיים למעסיק. ההוספה דולגה.', 'warning');
                    } else {
                        throw $e;
                    }
                }
            }
            // <<< passports quick-add
            flash('המעסיק עודכן.');
            redirect('employers/edit', ['id' => $id]);
        }
        $id_types  = $codes->employer_id_types();
        $cities    = $codes->cities();
        $genders   = $codes->genders();      // <- חדש
        $countries = $codes->countries();    // <- חדש
        $streets = $item['city_code'] ? $codes->streetsByCity((int)$item['city_code']) : [];
        require __DIR__ . '/../../views/employers/form.php';
    }
    public static function delete(PDO $pdo) {
        require_login('admin');
        $id = (int)($_GET['id'] ?? 0);
        // ensure no placements exist for this employer before deleting
        if ((new Placement($pdo))->countActiveByEmployer($id) > 0) {
            flash('לא ניתן למחוק מעסיק עם שיבוץ פעיל.', 'danger');
            redirect('employers/index');
        }
        (new Employer($pdo))->delete($id);
        flash('המעסיק נמחק.');
        redirect('employers/index');
    }
    public static function show(PDO $pdo) {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new Employer($pdo);
        $item = $m->find($id);
        // >>> passports: employer
        $st = $pdo->prepare("SELECT * FROM employer_passports WHERE employer_id = ? ORDER BY is_primary DESC, (expiry_date IS NULL) DESC, expiry_date DESC, id DESC");
        $st->execute([$id]);
        $employer_passports = $st->fetchAll(PDO::FETCH_ASSOC);
        // <<< passports
        if (!$item) { flash('מעסיק לא נמצא.', 'danger'); redirect('employers/index'); }
        $codes     = new CodeTables($pdo);
        $countries = $codes->countries();
        $cities    = $codes->cities();
        $streets = $item['city_code'] ? $codes->streetsByCity((int)$item['city_code']) : [];
        $genders   = $codes->genders();
        $id_types  = $codes->employer_id_types();
        require __DIR__ . '/../../views/employers/show.php';
    }
}
