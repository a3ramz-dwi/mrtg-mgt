<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'MRTG Mgt') ?> - Login</title>
  <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container" style="max-width: 420px; margin-top: 10vh;">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Login</h4>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/login">
          <?php echo \App\Services\Auth\CsrfService::fieldHtml(); ?>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" type="password" name="password" autocomplete="current-password" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>
      </div>
    </div>
    <div class="text-muted small mt-3">
      <?= htmlspecialchars($_ENV['APP_NAME'] ?? '') ?> • <?= htmlspecialchars($_ENV['APP_ENV'] ?? '') ?>
    </div>
  </div>
</body>
</html>
