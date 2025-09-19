<?php
/**
 * מודל: מסמכים למעסיק (EmployerDocument)
 * שימוש בטבלת employee_documents כהיסטורית — העמודה employee_id משמשת כאן כ-owner_id של המעסיק.
 * אחריות: DB בלבד (CRUD). ללא טיפול בקבצים פיזיים.
 */
class EmployerDocument
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function find(int $id): ?array
    {
        $st = $this->pdo->prepare("SELECT * FROM employee_documents WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * רשימת מסמכים לפי מעסיק וקישור מודול ספציפי (לדוגמה employer_passports + passport_id)
     */
    public function listFor(int $employerId, string $relatedTable, int $relatedId): array
    {
        $st = $this->pdo->prepare("SELECT *
                                     FROM employee_documents
                                    WHERE employee_id = :oid
                                      AND related_table = :rt
                                      AND related_id = :rid
                                    ORDER BY created_at DESC, id DESC");
        $st->execute([
            ':oid' => $employerId,
            ':rt'  => $relatedTable,
            ':rid' => $relatedId,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * יצירה
     * $data כולל: employer_id (ימופה ל-employee_id), related_table, related_id, doc_type, file_path, issued_at, expires_at, notes, uploaded_by
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO employer_documents
                   (employer_id, related_table, related_id, doc_type, file_path, issued_at, expires_at, notes, uploaded_by)
                VALUES
                   (:owner_id, :related_table, :related_id, :doc_type, :file_path, :issued_at, :expires_at, :notes, :uploaded_by)";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':owner_id'     => (int)$data['employer_id'],
            ':related_table'=> $data['related_table'] ?? null,
            ':related_id'   => isset($data['related_id']) ? (int)$data['related_id'] : null,
            ':doc_type'     => (string)$data['doc_type'],
            ':file_path'    => (string)$data['file_path'],
            ':issued_at'    => $data['issued_at'] ?: null,
            ':expires_at'   => $data['expires_at'] ?: null,
            ':notes'        => ($data['notes'] ?? '') ?: null,
            ':uploaded_by'  => isset($data['uploaded_by']) ? (int)$data['uploaded_by'] : null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare("DELETE FROM employer_documents WHERE id = ?");
        $st->execute([$id]);
    }
}
?>