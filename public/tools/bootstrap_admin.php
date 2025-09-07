<?php
require __DIR__ . '/../../src/db.php';
require __DIR__ . '/../../src/helpers.php';
require __DIR__ . '/../../src/models/User.php';
$pdo = db();
$u = new User($pdo);
$count = $u->count();
if ($count > 0) {
    echo "Admin already exists. Delete all users to use this bootstrap again.";
    exit;
}
$msg = '';
if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $display = trim($_POST['display_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($username && $password) {
        $u->create($username, $password, 'admin', $display ?: null, $email ?: null);
        $msg = "Admin user created. You can now login.";
    } else {
        $msg = "Please fill username and password.";
    }
}
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<title>Bootstrap Admin</title>
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h1 class="h4 mb-3">יצירת משתמש אדמין ראשון</h1>
          <?php if ($msg): ?>
            <div class="alert alert-info"><?= e($msg) ?></div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">שם משתמש</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">סיסמה</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">שם להצגה (לא חובה)</label>
              <input type="text" name="display_name" class="form-control">
            </div>
            <div class="mb-3">
              <label class="form-label">אימייל (לא חובה)</label>
              <input type="email" name="email" class="form-control">
            </div>
            <button class="btn btn-primary w-100">צור אדמין</button>
          </form>
          <p class="mt-3"><a href="../index.php?r=auth/login" class="link-secondary">&larr; מסך התחברות</a></p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
