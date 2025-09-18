<?php
class File
{
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }

    /** שליפת קבצים של רשומה ספציפית */
    public function forRecord(string $module, int $record_id): array
    {
        $stmt = $this->db->prepare("SELECT f.*, ft.name_he AS type_name
                                     FROM files f
                                     JOIN file_types ft ON ft.file_type_code=f.file_type_code
                                     WHERE f.module=:m AND f.record_id=:rid AND f.is_deleted=0
                                     ORDER BY f.uploaded_at DESC, f.file_id DESC");
        $stmt->execute([':m'=>$module, ':rid'=>$record_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** הוספת רשומת קובץ לאחר העלאה מוצלחת לדיסק */
    public function insert(array $data): int
    {
        $sql = "INSERT INTO files (module, record_id, file_type_code, original_name, stored_name, mime_type, size_bytes, checksum_sha1, notes, uploaded_by)
                VALUES (:module, :record_id, :file_type_code, :original_name, :stored_name, :mime_type, :size_bytes, :checksum_sha1, :notes, :uploaded_by)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':module'        => $data['module'],
            ':record_id'     => $data['record_id'],
            ':file_type_code'=> $data['file_type_code'],
            ':original_name' => $data['original_name'],
            ':stored_name'   => $data['stored_name'],
            ':mime_type'     => $data['mime_type'] ?? null,
            ':size_bytes'    => $data['size_bytes'] ?? null,
            ':checksum_sha1' => $data['checksum_sha1'] ?? null,
            ':notes'         => $data['notes'] ?? null,
            ':uploaded_by'   => $data['uploaded_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** מחיקת קובץ (לוגית – is_deleted=1) */
    public function softDelete(int $file_id): bool
    {
        $stmt = $this->db->prepare("UPDATE files SET is_deleted=1 WHERE file_id=:id");
        return $stmt->execute([':id'=>$file_id]);
    }

    /** שליפת קובץ לפי מזהה (לשם הורדה) */
    public function find(int $file_id): ?array
    {
        $stmt = $this->db->prepare("SELECT f.*, ft.name_he AS type_name FROM files f JOIN file_types ft ON ft.file_type_code=f.file_type_code WHERE f.file_id=:id AND f.is_deleted=0");
        $stmt->execute([':id'=>$file_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
?>