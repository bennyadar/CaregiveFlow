<?php
class FileType
{
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }

    /** מחזיר רשימת סוגי קבצים פעילים למילוי <select> */
    public function allActive(): array
    {
        $stmt = $this->db->prepare("SELECT file_type_code, name_he, name_en FROM file_types WHERE is_active=1 ORDER BY sort_order, name_he");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
?>