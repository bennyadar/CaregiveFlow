<?php require __DIR__ . '/../layout/header.php'; ?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">התחברות למערכת</h1>
        <p class="text-muted">אם זו הפעם הראשונה, צור אדמין ב- <a href="tools/bootstrap_admin.php">Bootstrap Admin</a></p>
        <form method="post" novalidate>
          <div class="mb-3">
            <label class="form-label">שם משתמש</label>
            <input type="text" name="username" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">סיסמה</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-primary w-100">כניסה</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
