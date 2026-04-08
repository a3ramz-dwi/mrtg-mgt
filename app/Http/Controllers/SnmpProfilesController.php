<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\SnmpProfile;
use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use App\Support\View;

final class SnmpProfilesController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);
    $q = trim((string)($_GET['q'] ?? ''));

    $res = SnmpProfile::paginate($page, $perPage, $q);

    View::render('snmp_profiles/index.php', [
      'pageTitle' => 'SNMP Profiles',
      'rows' => $res['rows'],
      'total' => $res['total'],
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
    ]);
  }

  public function create(): void
  {
    AuthMiddleware::requireLogin();

    View::render('snmp_profiles/form.php', [
      'pageTitle' => 'Add SNMP Profile',
      'mode' => 'create',
      'profile' => null,
    ]);
  }

  public function store(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $payload = $this->payloadFromPost();
    $id = SnmpProfile::create($payload);

    AuditService::log(
      $auth->userId(),
      'snmp_profile.create',
      'Created SNMP profile',
      'snmp_profile',
      $id,
      ['name' => $payload['name'], 'version' => $payload['version']]
    );

    header('Location: /snmp-profiles?created=' . $id);
    exit;
  }

  public function edit(): void
  {
    AuthMiddleware::requireLogin();

    $id = (int)($_GET['id'] ?? 0);
    $profile = SnmpProfile::find($id);
    if (!$profile) {
      http_response_code(404);
      echo "SNMP Profile not found";
      return;
    }

    View::render('snmp_profiles/form.php', [
      'pageTitle' => 'Edit SNMP Profile',
      'mode' => 'edit',
      'profile' => $profile,
    ]);
  }

  public function update(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $payload = $this->payloadFromPost();
    SnmpProfile::update($id, $payload);

    AuditService::log(
      $auth->userId(),
      'snmp_profile.update',
      'Updated SNMP profile',
      'snmp_profile',
      $id,
      ['name' => $payload['name'], 'version' => $payload['version']]
    );

    header('Location: /snmp-profiles?updated=' . $id);
    exit;
  }

  public function delete(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $old = SnmpProfile::find($id);
    SnmpProfile::delete($id);

    AuditService::log(
      $auth->userId(),
      'snmp_profile.delete',
      'Deleted SNMP profile',
      'snmp_profile',
      $id,
      ['name' => $old['name'] ?? null]
    );

    header('Location: /snmp-profiles?deleted=' . $id);
    exit;
  }

  private function payloadFromPost(): array
  {
    $toNull = fn($v) => ($v === '' || $v === null) ? null : $v;

    $version = (string)($_POST['version'] ?? '2c');

    // v2c focus: require community if version != 3
    $community = $toNull(trim((string)($_POST['community'] ?? '')));
    if ($version !== '3' && ($community === null || $community === '')) {
      // fallback safe default; you can add validation UI later
      $community = 'public';
    }

    $timeoutMs = (int)($_POST['timeout_ms'] ?? 1500);
    if ($timeoutMs < 200) $timeoutMs = 200;
    if ($timeoutMs > 30000) $timeoutMs = 30000;

    $retries = (int)($_POST['retries'] ?? 1);
    if ($retries < 0) $retries = 0;
    if ($retries > 10) $retries = 10;

    return [
      'name' => trim((string)($_POST['name'] ?? '')),
      'version' => $version,
      'community' => $community,

      // v3 fields (kept for future)
      'v3_sec_level' => $toNull((string)($_POST['v3_sec_level'] ?? '')),
      'v3_auth_user' => $toNull(trim((string)($_POST['v3_auth_user'] ?? ''))),
      'v3_auth_proto' => $toNull((string)($_POST['v3_auth_proto'] ?? '')),
      'v3_auth_pass' => $toNull((string)($_POST['v3_auth_pass'] ?? '')),
      'v3_priv_proto' => $toNull((string)($_POST['v3_priv_proto'] ?? '')),
      'v3_priv_pass' => $toNull((string)($_POST['v3_priv_pass'] ?? '')),

      'timeout_ms' => $timeoutMs,
      'retries' => $retries,
    ];
  }
}
