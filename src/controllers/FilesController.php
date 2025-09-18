<?php
require_once __DIR__ . '/../models/File.php';
require_once __DIR__ . '/../models/FileType.php';
require_once __DIR__ . '/../services/FileUploadService.php';
require_once __DIR__ . '/../helpers.php'; // עבור פונקציות e(), redirect(), וכו' אם קיימות

class FilesController
{
    /** רשימת קבצים של רשומה */
    public static function index(PDO $db)
    {
        $module    = $_GET['module']    ?? '';
        $record_id = (int)($_GET['record_id'] ?? 0);
        if (!$module || !$record_id) { http_response_code(400); echo 'Missing module/record_id'; return; }

        $fileModel = new File($db);
        $files     = $fileModel->forRecord($module, $record_id);

        // הצג partial לרשימה
        $types = (new FileType($db))->allActive();
        require __DIR__ . '/../../views/files/_list.php';
    }

    /** העלאת קובץ ושמירה ב-DB */
    public static function upload(PDO $db)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method Not Allowed'; return; }

        $module        = $_POST['module']        ?? '';
        $record_id     = (int)($_POST['record_id'] ?? 0);
        $file_type     = $_POST['file_type_code'] ?? '';
        $notes         = trim($_POST['notes'] ?? '') ?: null;
        $uploaded_by   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null; // אם יש

        if (!$module || !$record_id || !$file_type || empty($_FILES['attachment'])) {
            $_SESSION['flash_error'] = 'נא למלא את כל השדות ולבחור קובץ';
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
            return;
        }

        try {
            $svc  = new FileUploadService();
            $info = $svc->handleUpload($_FILES['attachment']);

            $fileModel = new File($db);
            $fileModel->insert([
                'module'         => $module,
                'record_id'      => $record_id,
                'file_type_code' => $file_type,
                'original_name'  => $_FILES['attachment']['name'],
                'stored_name'    => $info['stored_name'],
                'mime_type'      => $info['mime'],
                'size_bytes'     => $info['size'],
                'checksum_sha1'  => $info['sha1'],
                'notes'          => $notes,
                'uploaded_by'    => $uploaded_by,
            ]);

            $_SESSION['flash_success'] = 'קובץ הועלה בהצלחה';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'שגיאה בהעלאת קובץ: ' . $e->getMessage();
        }

        // חזרה לדף הקודם כדי להישאר בהקשר של המודול
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    }

    /** הורדת קובץ */
    public static function download(PDO $db)
    {
        $file_id = (int)($_GET['id'] ?? 0);
        if (!$file_id) { http_response_code(400); echo 'Missing file id'; return; }

        $fileModel = new File($db);
        $row = $fileModel->find($file_id);
        if (!$row) { http_response_code(404); echo 'File not found'; return; }

        $svc = new FileUploadService();
        $path = $svc->pathFor($row['stored_name']);
        if (!is_file($path)) { http_response_code(410); echo 'File missing from storage'; return; }

        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($row['original_name']) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    /** מחיקה (לוגית) */
    public static function delete(PDO $db)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Method Not Allowed'; return; }
        $file_id = (int)($_POST['id'] ?? 0);
        if (!$file_id) { http_response_code(400); echo 'Missing file id'; return; }

        $fileModel = new File($db);
        $ok = $fileModel->softDelete($file_id);
        $_SESSION['flash_' . ($ok ? 'success' : 'error')] = $ok ? 'הקובץ הוסר' : 'שגיאה בהסרה';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    }
}
?>