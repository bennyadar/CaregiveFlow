<?php
require_once __DIR__ . '/../models/Placement.php';

class PlacementsController {
    public static function index(PDO $pdo) {
        require_login();
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20; $offset = ($page-1)*$limit;
        $m = new Placement($pdo);
        $rows = $m->all($q, $limit, $offset);
        $total = $m->count($q);
        $cols = $m->columns();
        require __DIR__ . '/../../views/placements/index.php';
    }

    public static function create(PDO $pdo) {
        require_login();
        $selected_employee_id = null;
        if (isset($_GET['employee_id']) && $_GET['employee_id'] !== '') {
            $selected_employee_id = (int)$_GET['employee_id'];
        }
        $m = new Placement($pdo);
        $cols = $m->columns();
        if (is_post()) {
            $eid = (int)($_POST['employee_id'] ?? 0);
            $erid = (int)($_POST['employer_id'] ?? 0);
            $start = $_POST['start_date'] ?? null;
            $end = $_POST['end_date'] ?? null;
            if (!$eid || !$erid || !$start) {
                flash('שדות חובה: עובד, מעסיק, תאריך התחלה.', 'danger');
            } else if ($m->hasOverlap($eid, $start, $end, null)) {
                flash('קיימת חפיפה עם שיבוץ אחר של העובד בטווח תאריכים זה.', 'danger');
            } else {
                $id = $m->create($_POST);
                flash('השיבוץ נשמר בהצלחה!');
                redirect('placements/edit', ['id' => $id]);
            }
        }
        $employees = self::employeesSelect($pdo);
        $employers = self::employersSelect($pdo);
        $item = [];
        require __DIR__ . '/../../views/placements/form.php';
    }

    public static function edit(PDO $pdo) {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new Placement($pdo);
        $cols = $m->columns();
        $item = $m->find($id);
        if (!$item) { flash('שיבוץ לא נמצא.', 'danger'); redirect('placements/index'); }
        if (is_post()) {
            $eid = (int)$_POST['employee_id'];
            $erid = (int)$_POST['employer_id'];
            $start = $_POST['start_date'] ?? null;
            $end = $_POST['end_date'] ?? null;
            if (!$eid || !$erid || !$start) {
                flash('שדות חובה: עובד, מעסיק, תאריך התחלה.', 'danger');
            } else if ($m->hasOverlap($eid, $start, $end, $id)) {
                flash('קיימת חפיפה עם שיבוץ אחר של העובד בטווח תאריכים זה.', 'danger');
            } else {
                $m->update($id, $_POST);
                flash('השיבוץ עודכן.');
                redirect('placements/edit', ['id' => $id]);
            }
        }
        $employees = self::employeesSelect($pdo);
        $employers = self::employersSelect($pdo);
        require __DIR__ . '/../../views/placements/form.php';
    }

    public static function delete(PDO $pdo) {
        require_login('admin');
        $id = (int)($_POST['id'] ?? 0);
        (new Placement($pdo))->delete($id);
        flash('השיבוץ נמחק.');
        redirect('placements/index');
    }

    public static function show(PDO $pdo) {
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new Placement($pdo);
        $cols = $m->columns();
        $item = $m->find($id);
        if (!$item) { flash('שיבוץ לא נמצא.', 'danger'); redirect('placements/index'); }
        require __DIR__ . '/../../views/placements/show.php';
    }

    public static function employeesSelect(PDO $pdo): array {
        return $pdo->query("SELECT id, first_name, last_name, passport_number FROM employees ORDER BY last_name, first_name")->fetchAll();
    }
    public static function employersSelect(PDO $pdo): array {
        return $pdo->query("SELECT id, first_name, last_name, id_number FROM employers ORDER BY last_name, first_name")->fetchAll();
    }
}
