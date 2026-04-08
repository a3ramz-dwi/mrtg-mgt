<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Auth\AuthService;
use App\Support\View;

final class DashboardController
{
  public function index(): void
  {
    (new AuthService())->requireLogin();

    View::render('dashboard/index.php', [
      'pageTitle' => 'Dashboard',
    ]);
  }
}
