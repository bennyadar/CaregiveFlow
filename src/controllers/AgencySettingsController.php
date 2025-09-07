<?php
require_once __DIR__ . '/../models/AgencySetting.php';

class AgencySettingsController {
    public static function index(PDO $pdo){
        require_login();
        $m = new AgencySetting($pdo);
        $rows = $m->all();
        require __DIR__ . '/../../views/agency_settings/index.php';
    }

    public static function show(PDO $pdo){
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new AgencySetting($pdo);
        $item = $m->find($id);
        if (!$item) { flash('רשומה לא נמצאה', 'danger'); redirect('agency_settings/index'); }
        require __DIR__ . '/../../views/agency_settings/show.php';
    }

    public static function create(PDO $pdo){
        require_login();
        $item = null;
        require __DIR__ . '/../../views/agency_settings/form.php';
    }

    public static function store(PDO $pdo){
        require_login();
        $m = new AgencySetting($pdo);
        $id = $m->create($_POST);
        flash('נוצרה רשומת לשכה.');
        redirect('agency_settings/show', ['id' => $id]);
    }

    public static function edit(PDO $pdo){
        require_login();
        $id = (int)($_GET['id'] ?? 0);
        $m = new AgencySetting($pdo);
        $item = $m->find($id);
        if (!$item) { flash('רשומה לא נמצאה', 'danger'); redirect('agency_settings/index'); }
        require __DIR__ . '/../../views/agency_settings/form.php';
    }

    public static function update(PDO $pdo){
        require_login();
        $id = (int)($_POST['id'] ?? 0);
        $m = new AgencySetting($pdo);
        $m->update($id, $_POST);
        flash('עודכן בהצלחה.');
        redirect('agency_settings/show', ['id' => $id]);
    }

    public static function destroy(PDO $pdo){
        require_login();
        $id = (int)($_POST['id'] ?? 0);
        $m = new AgencySetting($pdo);
        $m->delete($id);
        flash('נמחק.');
        redirect('agency_settings/index');
    }
}
