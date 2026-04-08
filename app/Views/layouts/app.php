<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'MRTG Mgt') ?> - <?= htmlspecialchars($pageTitle ?? '') ?></title>
  <link href="/assets/vendor/bootstrap.min.css" rel="stylesheet">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>
  <div class="d-flex">
    <?php require __DIR__ . '/../partials/sidebar.php'; ?>
    <main class="flex-grow-1 p-3">
      <?php require $viewFile; ?>
    </main>
  </div>
  <script src="/assets/vendor/bootstrap.bundle.min.js"></script>
</body>
</html>
