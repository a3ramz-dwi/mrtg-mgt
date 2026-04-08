<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Support\View;

final class ComingSoonController
{
  public function eventTimeline(): void
  {
    $this->render('Event Timeline', 'This page will show event timeline and alerts aggregation.');
  }

  public function settings(): void
  {
    $this->render('Settings', 'Application settings will be managed here (env, paths, polling, retention).');
  }

  public function users(): void
  {
    $this->render('Users', 'User management (create/disable/reset password) will be implemented here.');
  }

  public function roles(): void
  {
    $this->render('Roles', 'Role-based access control (RBAC) will be implemented here.');
  }

  private function render(string $title, string $desc): void
  {
    AuthMiddleware::requireLogin();

    View::render('coming_soon/index.php', [
      'pageTitle' => $title,
      'title' => $title,
      'desc' => $desc,
    ]);
  }
}
