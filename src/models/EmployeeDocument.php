<?php
/**
 * מודל: מסמכים לעובד (employee_documents)
 * אחראי לפעולות DB בלבד (CRUD) – ללא טיפול בקבצים עצמם.
 */
class EmployeeDocument
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
     * רשימת מסמכים לפי עובד וקישור מודול ספציפי (למשל employee_passports + passport_id)
     */
    public function listFor(int $employeeId, string $relatedTable, int $relatedId): array
    {
        $st = $this->pdo->prepare("SELECT *
                                     FROM employee_documents
                                    WHERE employee_id = :eid
                                      AND related_table = :rt
                                      AND related_id = :rid
                                    ORDER BY created_at DESC, id DESC");
        $st->execute([
            ':eid' => $employeeId,
            ':rt'  => $relatedTable,
            ':rid' => $relatedId,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * יצירה
     * $data כולל: employee_id, related_table, related_id, doc_type, file_path, issued_at, expires_at, notes, uploaded_by
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO employee_documents
                   (employee_id, related_table, related_id, doc_type, file_path, issued_at, expires_at, notes, uploaded_by)
                VALUES
                   (:employee_id, :related_table, :related_id, :doc_type, :file_path, :issued_at, :expires_at, :notes, :uploaded_by)";
        $st = $this->pdo->prepare($sql);
        $st->execute([
            ':employee_id'  => (int)$data['employee_id'],
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
        $st = $this->pdo->prepare("DELETE FROM employee_documents WHERE id = ?");
        $st->execute([$id]);
    }
}