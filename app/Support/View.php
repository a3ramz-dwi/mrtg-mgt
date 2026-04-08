<?php
declare(strict_types=1);

namespace App\Support;

final class View
{
  public static function render(string $viewFile, array $vars = []): void
  {
    extract($vars, EXTR_SKIP);
    $viewFileAbs = __DIR__ . '/../Views/' . ltrim($viewFile, '/');
    if (!is_file($viewFileAbs)) {
      http_response_code(500);
      echo "View not found: " . htmlspecialchars($viewFile);
      return;
    }

    // Layout wrapper expects $pageTitle and $viewFile
    $viewFile = $viewFileAbs;
    require __DIR__ . '/../Views/layouts/app.php';
  }
}
