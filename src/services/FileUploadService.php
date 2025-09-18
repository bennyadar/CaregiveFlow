<?php
class FileUploadService
{
    // תיקיית יעד יחסית ל-public/ (כדי לאפשר הורדה ישירה בקישור)
    public const UPLOAD_DIR = __DIR__ . '/../../public/uploads';

    /**
     * בדיקות בסיסיות: גודל קובץ, הרחבות, תקינות העלאה
     * החזר: [stored_name, mime, size, sha1]
     */
    public function handleUpload(array $file): array
    {
        // תוקף
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Upload parameters invalid');
        }
        switch ($file['error']) {
            case UPLOAD_ERR_OK: break;
            case UPLOAD_ERR_NO_FILE: throw new RuntimeException('לא נבחר קובץ');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE: throw new RuntimeException('הקובץ גדול מדי');
            default: throw new RuntimeException('שגיאה בהעלאה');
        }

        // הגבלות גודל (לדוגמה 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new RuntimeException('הקובץ חורג מהמקסימום (10MB)');
        }

        // יצירת תיקייה אם צריך
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0775, true);
        }

        // שם ייחודי בדיסק – מונע התנגשויות ושומר על קבצים בטוחים
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique = bin2hex(random_bytes(16));
        $storedName = $unique . ($ext ? ('.' . strtolower($ext)) : '');
        $dest = self::UPLOAD_DIR . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('נכשלה העברת הקובץ לשרת');
        }

        // mime + checksum
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $dest) ?: null;
        finfo_close($finfo);
        $sha1  = sha1_file($dest);

        return [
            'stored_name' => $storedName,
            'mime'        => $mime,
            'size'        => (int)$file['size'],
            'sha1'        => $sha1,
        ];
    }

    /** מחיקה פיזית בטוחה – נקרא לאחר soft delete או בעת ניקוי */
    public function deletePhysical(string $stored_name): bool
    {
        $path = self::UPLOAD_DIR . '/' . $stored_name;
        return is_file($path) ? @unlink($path) : true; // אם לא קיים – נחשב הצלחה
    }

    /** החזרת נתיב מלא לקובץ להורדה */
    public function pathFor(string $stored_name): string
    {
        return self::UPLOAD_DIR . '/' . $stored_name;
    }
}
?>