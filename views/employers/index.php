<?php
/**
 * רשימת מעסיקים
 * דפוס UI: Index (כותרת + חיפוש/סינון + טבלה + פעולות)
 */
?>

<?php require __DIR__ . '/../layout/header.php'; ?>

<?php
// ===== כותרת מסך + כפתורי פעולה =====
$title = 'מעסיקים';
$rightHtml = '<a class="btn btn-success" href="?r=employers/create">+ מעסיק חדש</a>';
include __DIR__ . '/../partials/page_header.php';

// ===== חיפוש בסיסי =====
$routeValue = 'employers';
$label = 'חיפוש (ת.ז / שם)';
$qValue = $_GET['q'] ?? '';
include __DIR__ . '/../partials/search_bar.php';
?>

<div class="table-responsive">
  <table class="table table-striped table-hover align-middle">
    <thead>
      <tr>
        <th>#</th>
        <th>שם</th>
        <th>ת"ז</th>
        <th>טלפון</th>
        <th>אימייל</th>
        <th class="text-end">פעולות</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach (($rows ?? []) as $r): ?>
        <?php $id = (int)($r['id'] ?? 0); ?>
        <tr>
          <td><?= $id ?></td>
          <td><?= e(trim(($r['last_name'] ?? '') . ' ' . ($r['first_name'] ?? ''))) ?></td>
          <td><?= e($r['id_number'] ?? '—') ?></td>
          <td><?= e($r['phone'] ?? '—') ?></td>
          <td><?= e($r['email'] ?? '—') ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="?r=employers/show&id=<?= $id ?>">צפייה</a>
            <a class="btn btn-sm btn-outline-primary" href="?r=employers/edit&id=<?= $id ?>">עריכה</a>
            <?php if ((current_user()['role'] ?? '') === 'admin'): ?>
              <a class="btn btn-sm btn-outline-danger" href="?r=employers/delete&id=<?= $id ?>" onclick="return confirm('למחוק?');">מחיקה</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
