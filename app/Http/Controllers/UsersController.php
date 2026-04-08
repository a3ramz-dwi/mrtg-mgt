<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Auth\AuthService;
use App\Support\DB;
use App\Support\View;

final class UsersController
{
  public function index(): void
  {
    AuthMiddleware::requireRole('admin');

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);
    $q = trim((string)($_GET['q'] ?? ''));

    $res = User::paginate($page, $perPage, $q);

    // map roles for each user (N+1 OK for small user count)
    foreach ($res['rows'] as &$r) {
      $r['_roles'] = User::roles((int)$r['id']);
    }

    View::render('users/index.php', [
      'pageTitle' => 'Users',
      'rows' => $res['rows'],
      'total' => $res['total'],
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
    ]);
  }

  public function createForm(): void
  {
    AuthMiddleware::requireRole('admin');

    View::render('users/create.php', [
      'pageTitle' => 'Create User',
      'roles' => Role::all(),
      'error' => null,
    ]);
  }

  public function create(): void
  {
    AuthMiddleware::requireRole('admin');
    $auth = new AuthService();

    $username = trim((string)($_POST['username'] ?? ''));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $roleIds = $_POST['role_ids'] ?? [];
    if (!is_array($roleIds)) $roleIds = [];

    if ($username === '' || $password === '') {
      View::render('users/create.php', [
        'pageTitle' => 'Create User',
        'roles' => Role::all(),
        'error' => 'username and password required',
      ]);
      return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $pdo = DB::pdo();
    $pdo->prepare("INSERT INTO users (username, full_name, email, password_hash, is_active) VALUES (?, ?, ?, ?, ?)")
      ->execute([$username, $fullName !== '' ? $fullName : null, $email !== '' ? $email : null, $hash, $isActive]);

    $uid = (int)$pdo->lastInsertId();
    User::setRoles($uid, $roleIds);

    AuditService::log($auth->userId(), 'rbac.user_create', 'Created user', 'user', $uid, ['username' => $username]);

    header('Location: /users');
    exit;
  }

  public function editForm(): void
  {
    AuthMiddleware::requireRole('admin');

    $id = (int)($_GET['id'] ?? 0);
    $u = User::find($id);
    if (!$u) { http_response_code(404); echo "User not found"; return; }

    View::render('users/edit.php', [
      'pageTitle' => 'Edit User',
      'u' => $u,
      'roles' => Role::all(),
      'userRoleNames' => User::roles($id),
      'error' => null,
    ]);
  }

  public function update(): void
  {
    AuthMiddleware::requireRole('admin');
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $u = User::find($id);
    if (!$u) { http_response_code(404); echo "User not found"; return; }

    $username = trim((string)($_POST['username'] ?? ''));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? ''); // optional
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $roleIds = $_POST['role_ids'] ?? [];
    if (!is_array($roleIds)) $roleIds = [];

    if ($username === '') {
      View::render('users/edit.php', [
        'pageTitle' => 'Edit User',
        'u' => $u,
        'roles' => Role::all(),
        'userRoleNames' => User::roles($id),
        'error' => 'username required',
      ]);
      return;
    }

    $pdo = DB::pdo();

    if ($password !== '') {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, password_hash=?, is_active=? WHERE id=?")
        ->execute([$username, $fullName !== '' ? $fullName : null, $email !== '' ? $email : null, $hash, $isActive, $id]);
    } else {
      $pdo->prepare("UPDATE users SET username=?, full_name=?, email=?, is_active=? WHERE id=?")
        ->execute([$username, $fullName !== '' ? $fullName : null, $email !== '' ? $email : null, $isActive, $id]);
    }

    User::setRoles($id, $roleIds);

    AuditService::log($auth->userId(), 'rbac.user_update', 'Updated user', 'user', $id, ['username' => $username]);

    header('Location: /users');
    exit;
  }

  public function delete(): void
  {
    AuthMiddleware::requireRole('admin');
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { header('Location: /users'); exit; }

    DB::pdo()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

    AuditService::log($auth->userId(), 'rbac.user_delete', 'Deleted user', 'user', $id);

    header('Location: /users');
    exit;
  }
}