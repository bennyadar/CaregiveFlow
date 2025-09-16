<?php /**
 * רשימת עובדים – עמודות מותאמות
 * מציג: שם, דרכון, טלפון, אימייל, דרכון - פקיעת תוקף, ויזה - פקיעת תוקף, ביטוח - פקיעת תוקף, פעולות
 *
 * נדרש מה-Controller להזרים לכל שורה את המפתחות:
 *   id, first_name, last_name, passport_number, phone, email,
 *   passport_expiry_date, visa_expiry_date, insurance_expiry_date
 */ ?>
<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="container" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">עובדים</h3>
        <a class="btn btn-success" href="?r=employees/create">+ עובד חדש</a>
    </div>

    <!-- סינון בסיסי -->
    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="r" value="employees">
        <div class="col-md-4">
            <label class="form-label">חיפוש (שם/דרכון/טלפון/מעסיק)</label>
            <input type="text" name="q" class="form-control" value="<?= e($_GET['q'] ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-secondary w-100">סינון</button>
        </div>
    </form>

    <div class="table-responsive overflow-visible">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>שם עובד</th>
                    <th>דרכון</th>
                    <th>טלפון</th>
                    <th>מעסיק נוכחי</th>
                    <th>דרכון - תוקף</th>
                    <th>ויזה - תוקף</th>
                    <th>ביטוח - תוקף</th>
                    <th class="text-end">פעולות</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($rows ?? []) as $row): ?>
                <?php
                    $full_name = trim(($row['last_name'] ?? '').' '.($row['first_name'] ?? ''));
                    $phone     = $row['phone'] ?? '';
                    $email     = $row['email'] ?? '';
                    $pass_num  = $row['passport_number'] ?? '';

                    $pass_exp  = $row['passport_expiry_date']   ?? null; // פקיעת דרכון
                    $visa_exp  = $row['visa_expiry_date']       ?? null; // פקיעת ויזה
                    $ins_exp   = $row['insurance_expiry_date']  ?? null; // פקיעת ביטוח
                    
                    $fmt = static function($d) {
                        if (!$d) return '<span class="text-muted">—</span>';
                        $today = date('Y-m-d');
                        if ($d < $today) return '<span class="text-danger fw-semibold">'.e($d).'</span>'; // פג
                        $days = (new DateTime($today))->diff(new DateTime($d))->days;
                        if ($days <= 30) return '<span class="text-warning fw-semibold">'.e($d).'</span>'; // בקרוב
                        return '<span class="text-success fw-semibold">'.e($d).'</span>'; // בתוקף
                    };
                ?>
                <tr>
                    <td><?= e($full_name ?: '—') ?></td>
                    <td><?= e($pass_num ?: '—') ?></td>
                    <td><?= e($phone ?: '—') ?></td>
                    <?php $employer_name = $row['current_employer_name'] ?? ''; ?>
                    <td><?= e($employer_name !== '' ? $employer_name : '—') ?></td>
                    <td><?= $fmt($pass_exp) ?></td>
                    <td><?= $fmt($visa_exp) ?></td>
                    <td><?= $fmt($ins_exp) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="?r=employees/show&id=<?= e($row['id']) ?>">צפייה</a>
                        <a class="btn btn-sm btn-outline-primary" href="?r=employees/edit&id=<?= e($row['id']) ?>">עריכה</a>
                        <!-- ייצוא: מרוכז ל-dropdown אחד -->
                        <div class="btn-group btn-group-sm" role="group">
                          <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            ייצוא
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end" dir="rtl">
                            <li>
                              <a class="dropdown-item" href="index.php?r=exports/piba&employee_id=<?= (int)$row['id'] ?>">
                                לרשות האוכלוסין (PIBA)
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item" href="index.php?r=exports/bafi&employee_id=<?= (int)$row['id'] ?>">
                                למת״ש (BAFI)
                              </a>
                            </li>
                          </ul>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="?r=placements/create&employee_id=<?= e($row['id']) ?>">שיבוץ</a>
                        <a class="btn btn-sm btn-outline-danger" href="?r=employees/delete&id=<?= e($row['id']) ?>" onclick="return confirm('למחוק?');">מחיקה</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pages) && $pages > 1): ?>
        <nav>
            <ul class="pagination">
                <?php for ($p=1; $p <= $pages; $p++): ?>
                    <?php $active = ($p === ($page ?? 1)) ? 'active' : ''; ?>
                    <li class="page-item <?= $active ?>">
                        <a class="page-link" href="<?= e(update_query(['page'=>$p])) ?>"><?= e($p) ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
