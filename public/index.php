<?php
declare(strict_types=1);

// קבצי Bootstrap כפי שקיים אצלך
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/helpers.php';

// יצירת PDO דרך הפונקציה אצלך
$pdo = db();

// קריאת ראוט בפורמט controller/action (ברירת מחדל: dashboard/index)
$route = $_GET['r'] ?? 'dashboard/index';
[$ctrl, $action] = array_pad(explode('/', $route, 2), 2, 'index');
$action = strtok($action, '&?');                 // לוקח רק את שם הפעולה
$action = preg_replace('/[^A-Za-z0-9_]/', '', $action); // מסיר תווי זבל אם יש

// מיפוי קונטרולרים → קבצים (נשמר כפי שקיים אצלך, כולל Exports)
$map = [
    'auth'                  => __DIR__ . '/../src/controllers/AuthController.php',
    'employees'             => __DIR__ . '/../src/controllers/EmployeesController.php',
    'employers'             => __DIR__ . '/../src/controllers/EmployersController.php',
    'placements'            => __DIR__ . '/../src/controllers/PlacementsController.php',
    'reports'               => __DIR__ . '/../src/controllers/ReportsController.php',
    'dashboard'             => __DIR__ . '/../src/controllers/DashboardController.php',
    'agency_settings'       => __DIR__ . '/../src/controllers/AgencySettingsController.php',
    'exports'               => __DIR__ . '/../src/controllers/ExportsController.php',
    //'employee_passports'    => __DIR__ . '/../src/controllers/EmployeePassportsController.php',
    'employer_passports'    => __DIR__ . '/../src/controllers/EmployerPassportsController.php',
    'passports'             => __DIR__ . '/../src/controllers/PassportsController.php',
    'visas'                 => __DIR__ . '/../src/controllers/VisasController.php',
    'insurances'            => __DIR__ . '/../src/controllers/InsurancesController.php',
    'employment_permits'    => __DIR__ . '/../src/controllers/EmploymentPermitsController.php',
    'employer_fees'         => __DIR__ . '/../src/controllers/EmployerFeesController.php',
    'employer_fee_payments' => __DIR__ . '/../src/controllers/EmployerFeePaymentsController.php',
    'files'                 => __DIR__ . '/../src/controllers/FilesController.php',
];

// אם קונטרולר לא קיים במפה — 404
if (!isset($map[$ctrl])) {
    http_response_code(404);
    echo "Not Found";
    exit;
}

// טעינת קובץ הקונטרולר
require_once $map[$ctrl];

// בניית שם המחלקה מה־key (לפי הדפוס שיש אצלך)
$controllerClass = preg_replace('/[^A-Za-z0-9]/', '', ucwords(str_replace(['_', '-'], ' ', $ctrl))) . 'Controller';

// בדיקה שהמחלקה וה־action קיימים
if (!class_exists($controllerClass) || !method_exists($controllerClass, $action)) {
    http_response_code(404);
    echo "Not Found";
    exit;
}

// קריאה לפעולה הסטטית עם $pdo (כפי שקיים אצלך)
call_user_func([$controllerClass, $action], $pdo);
