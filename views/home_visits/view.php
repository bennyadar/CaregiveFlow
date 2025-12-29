<?php /** @var array $item, $emp, $documents, $docTypes; @var int|string|null $derived_status; @var int|null $days_left; */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="container" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי ביקור בית #<?= e($item['id']) ?></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="?r=home_visits/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <a class="btn btn-outline-secondary" href="?r=home_visits">חזרה לרשימה</a>
            <?php if (!empty($emp['id'])): ?>
                <a class="btn btn-outline-secondary" href="?r=employees/show&id=<?= e($emp['id']) ?>">חזרה לכרטיס עובד</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div><strong>עובד:</strong> <?= e($emp ? ($emp['first_name'].' '.$emp['last_name']) : '—') ?></div>
                    <div><strong>תאריך ביקור:</strong> <?= e($item['visit_date']) ?></div>
                    <div><strong>סוג:</strong> <?= e($item['type_name'] ?? '—') ?></div>
                    <div><strong>סטטוס:</strong> <?= e(($derived_status === 'overdue') ? 'באיחור' : ($item['status_name'] ?? '—')) ?></div>
                    <div><strong>שלב:</strong> <?= e($item['stage_name'] ?? '—') ?></div>
                </div>

                <div class="col-md-6">
                    <div><strong>מעקב נדרש:</strong> <?= !empty($item['followup_required']) ? 'כן' : 'לא' ?></div>
                    <div><strong>יעד ביקור הבא:</strong> <?= e($item['next_visit_due'] ?? '—') ?></div>
                    <div><strong>ימים עד יעד:</strong> <?= ($days_left === null) ? '—' : e($days_left) ?></div>

                    <?php if (!empty($item['employer_first_name']) || !empty($item['employer_last_name'])): ?>
                        <div class="mt-2"><strong>מעסיק:</strong> <?= e(($item['employer_first_name'] ?? '').' '.($item['employer_last_name'] ?? '')) ?></div>
                        <div><strong>ת"ז מעסיק:</strong> <?= e($item['employer_id_number'] ?? '—') ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <hr>
                    <div><strong>סיכום:</strong></div>
                    <div class="text-muted"><?= nl2br(e($item['summary'] ?? '')) ?></div>
                </div>

                <div class="col-12">
                    <div><strong>ממצאים:</strong></div>
                    <div class="text-muted"><?= nl2br(e($item['findings'] ?? '')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/_documents.php'; ?>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
