<?php /** @var array $item */ /** @var array|null $emp */ ?>
<?php require __DIR__.'/../layout/header.php'; ?>

<div class="container" dir="rtl">
    <!-- כותרת + פעולות -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="m-0">פרטי דמי תאגיד #<?= e($item['id']) ?></h3>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="?r=employer_fees/edit&id=<?= e($item['id']) ?>">עריכה</a>
            <?php if (!empty($_GET['employer_id'])): ?>
                <a class="btn btn-outline-secondary" href="?r=employer_fees&employer_id=<?= e($_GET['employer_id']) ?>">חזרה לדמי תאגיד</a>
            <?php else: ?>
                <a class="btn btn-outline-secondary" href="?r=employer_fees">חזרה לדמי תאגיד</a>
            <?php endif; ?>
            <?php if (!empty($emp['id'])): ?>
                <a class="btn btn-outline-secondary" href="?r=employers/show&id=<?= e($emp['id']) ?>">חזרה לכרטיס מעסיק</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- עמודה שמאלית: כרטיסי סטטוס/סוג/אמצעי/סכום/דדליין -->
        <div class="col-lg-7">
            <div class="row g-3">
                <!-- סטטוס -->
                <div class="col-sm-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold mb-1">סטטוס</div>
                                    <div class="small text-muted">מצב הרשומה</div>
                                </div>
                                <?php
                                  $statusName = $status_name ?? ($item['status_name'] ?? '');
                                  $badge = 'secondary';
                                  if (!empty($statusName)) {
                                      $s = mb_strtolower($statusName);
                                      if (str_contains($s,'שולם')) $badge='success';
                                      elseif (str_contains($s,'ממתין')||str_contains($s,'פתוח')) $badge='warning';
                                      elseif (str_contains($s,'בוטל')||str_contains($s,'פג')) $badge='danger';
                                  }
                                ?>
                                <span class="badge bg-<?= $badge ?> fs-6"><?= e($statusName ?: '—') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- סוג חיוב -->
                <div class="col-sm-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="fw-bold mb-1">סוג חיוב</div>
                            <div class="small text-muted">לפי קוד סוג</div>
                            <div class="mt-2"><?= e($type_name ?? ($item['fee_type_name'] ?? '—')) ?></div>
                        </div>
                    </div>
                </div>

                <!-- אמצעי תשלום -->
                <div class="col-sm-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="fw-bold mb-1">אמצעי תשלום</div>
                            <div class="small text-muted">כפי שנקלט</div>
                            <div class="mt-2"><?= e($payment_name ?? ($item['payment_method_name'] ?? '—')) ?></div>
                        </div>
                    </div>
                </div>

                <!-- סכום -->
                <div class="col-sm-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="fw-bold mb-1">סכום</div>
                            <div class="small text-muted">מטבע + ערך</div>
                            <div class="mt-2"><?= e($item['currency_code'] ?? 'ILS') ?> <?= number_format((float)($item['amount'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- דדליין (מועד חיוב/תאריך יעד) -->
                <div class="col-12">
                    <div class="card shadow-sm h-100">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold mb-1">מועד חיוב (תאריך יעד)</div>
                                <div class="small text-muted">התאריך האחרון לתשלום</div>
                            </div>
                            <div class="fs-5">
                                <?= e($item['due_date'] ?? '—') ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- עמודה ימנית: מעסיק + פרטים נוספים -->
        <div class="col-lg-5">
            <!-- כרטיס מעסיק קצר -->
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="fw-bold mb-1">מעסיק</div>
                    <div class="small text-muted">שם + מזהים</div>
                    <div class="mt-1">
                        <?= e(($emp['last_name'] ?? '').' '.($emp['first_name'] ?? '')) ?>
                        <?php if (!empty($emp['id_number'])): ?>
                            <span class="text-muted">(<?= e($emp['id_number']) ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- פרטי תשלום -->
            <div class="card shadow-sm">
                <div class="card-header">פרטי תשלום</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="mb-0"><strong>תקופת תשלום:</strong>
                                <?php if (!empty($item['payment_from_date']) || !empty($item['payment_to_date'])): ?>
                                    <?= e($item['payment_from_date'] ?? '—') ?> — <?= e($item['payment_to_date'] ?? '—') ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-0"><strong>שולם בפועל:</strong> <?= e($item['payment_date'] ?? '—') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-0"><strong>אסמכתא/קבלה:</strong> <?= e($item['reference_number'] ?? '—') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="mb-0"><strong>הערות:</strong><br><?= nl2br(e($item['notes'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require __DIR__.'/../layout/footer.php'; ?>
