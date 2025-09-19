<?php
/**
 * Service: טיפול בקבצים פיזיים + ולידציה בסיסית + יצירת נתיב
 * שים לב: ה-DB נשאר רק לאחסון/שליפה. השירות משתמש במודל לשמירת המטא-דאטה.
 */
class DocumentService
{
    private PDO $pdo;
    private string $uploadBase;

    public function __construct(PDO $pdo, ?string $uploadBase = null)
    {
        $this->pdo = $pdo;
        // מיקום ברירת מחדל: public/uploads
        $root = dirname(__DIR__, 2);
        $this->uploadBase = $uploadBase ?: ($root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
        if (!is_dir($this->uploadBase)) {
            @mkdir($this->uploadBase, 0775, true);
        }
    }

    /**
     * שמירת קובץ שהועלה בפועל + החזרת נתיב יחסי שישמר ב-DB
     * $file הוא $_FILES['file']
     */
    public function storeUploadedFile(array $file, int $employeeId, string $moduleKey, int $recordId, string $docType = 'file'): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('שגיאה בהעלאת הקובץ.');
        }
        // ולידציות בסיסיות
        $allowedExt = ['pdf','jpg','jpeg','png','gif','webp'];
        $maxBytes   = 15 * 1024 * 1024; // 15MB

        $origName = (string)$file['name'];
        $tmpPath  = (string)$file['tmp_name'];
        $size     = (int)$file['size'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException('סוג קובץ לא נתמך.');
        }
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('גודל קובץ חורג מהמותר.');
        }

        // מבנה תיקיות: /uploads/employees/{employeeId}/{moduleKey}/{recordId}/
        $targetDir = $this->uploadBase . DIRECTORY_SEPARATOR . 'employees' . DIRECTORY_SEPARATOR . $employeeId . DIRECTORY_SEPARATOR . $moduleKey . DIRECTORY_SEPARATOR . $recordId;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        // יצירת שם ייחודי על בסיס doc_type + timestamp + hash קצר
        $docSlug = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', strtolower($docType));
        $unique  = $docSlug . '_' . date('Ymd_His') . '_' . substr(sha1($origName . microtime(true)), 0, 8) . '.' . $ext;
        $dest    = $targetDir . DIRECTORY_SEPARATOR . $unique;

        if (!move_uploaded_file($tmpPath, $dest)) {
            throw new RuntimeException('שמירת הקובץ נכשלה.');
        }

        // נתיב יחסי לשמירה ב-DB (לטובת קישורים ב-View)
        $relative = str_replace($this->publicBase(), '', $dest);
        $relative = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $relative), '/');
        return '/' . $relative; // לדוגמה: /uploads/employees/12/passports/34/file.pdf
    }

    /**
     * מחיקת קובץ פיזי (במידה וקיים)
     */
    public function deletePhysical(string $relativePath): void
    {
        $abs = $this->publicBase() . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    private function publicBase(): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
    }
}