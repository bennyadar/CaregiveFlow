<?php
require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/models/CodeTables.php';
header('Content-Type: application/json; charset=UTF-8');
$city = (int)($_GET['city_code'] ?? 0);
$rows = [];
if ($city > 0) {
    $rows = (new CodeTables(db()))->streetsByCity($city);
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
