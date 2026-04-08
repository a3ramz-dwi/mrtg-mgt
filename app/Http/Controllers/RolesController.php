<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\Role;
use App\Services\Audit\AuditService;
use App\Services\Auth\AuthService;
use App\Support\View;

final class RolesController
{
  public function index(): void
  {
    AuthMiddleware::requireRole('admin');

    View::render('roles/index.php', [
      'pageTitle' => 'Roles',
      'rows' => Role::all(),
    ]);
  }

  public function createForm(): void
  {
    AuthMiddleware::requireRole('admin');

    View::render('roles/create.php', [
      'pageTitle' => 'Create Role',
      'error' => null,
    ]);
  }

  public function create(): void
  {
    AuthMiddleware::requireRole('admin');
    $auth = new AuthService();

    $name = trim((string)($_POST['name'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? ''));

    if ($name === '') {
      View::render('roles/create.php', [
        'pageTitle' => 'Create Role',
        'error' => 'name required',
      ]);
      return;
    }

    $id = Role::create($name, $desc);
    AuditService::log($auth->userId(), 'rbac.role_create', 'Created role', 'role', $id, ['name' => $name]);

    header('Location: /roles');
    exit;
  }

  public function editForm(): void
  {
    AuthMiddleware::requireRole('admin');

    $id = (int)($_GET['id'] ?? 0);
    $r = Role::find($id);
    if (!$r) { http_response_code(404); echo "Role not found"; return; }

    View::render('roles/edit.php', [
      'pageTitle' => 'Edit Role',
      'r' => $r,
      'error' => null,
    ]);
  }

  public function update(): void
  {
    AuthMiddleware::requireRole('admin');
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $r = Role::find($id);
    if (!$r) { http_response_code(404); echo "Role not found"; return; }

    $name = trim((string)($_POST['name'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? ''));

    if ($name === '') {
      View::render('roles/edit.php', [
        'pageTitle' => 'Edit Role',
        'r' => $r,
        'error' => 'name required',
      ]);
      return;
    }

    Role::update($id, $name, $desc);
    AuditService::log($auth->userId(), 'rbac.role_update', 'Updated role', 'role', $id, ['name' => $name]);

    header('Location: /roles');
    exit;
  }

  public function delete(): void
  {
    AuthMiddleware::requireRole('admin');
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { header('Location: /roles'); exit; }

    Role::delete($id);
    AuditService::log($auth->userId(), 'rbac.role_delete', 'Deleted role', 'role', $id);

    header('Location: /roles');
    exit;
  }
}