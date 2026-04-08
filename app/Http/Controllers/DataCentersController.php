<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\DataCenter;
use App\Services\Auth\AuthService;
use App\Services\Audit\AuditService;
use App\Support\View;

final class DataCentersController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);
    $q = trim((string)($_GET['q'] ?? ''));

    $res = DataCenter::paginate($page, $perPage, $q);

    View::render('data_centers/index.php', [
      'pageTitle' => 'Data Center',
      'rows' => $res['rows'],
      'total' => $res['total'],
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
      'widgets' => DataCenter::widgets(),
      'infoCards' => DataCenter::dcInfoCards(3),
    ]);
  }

  public function create(): void
  {
    AuthMiddleware::requireLogin();

    View::render('data_centers/form.php', [
      'pageTitle' => 'Add Data Center',
      'mode' => 'create',
      'dc' => null,
    ]);
  }

  public function store(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $payload = $this->payloadFromPost();
    $id = DataCenter::create($payload);

    AuditService::log(
      $auth->userId(),
      'data_center.create',
      'Created data center',
      'data_center',
      $id,
      ['name' => $payload['name'], 'category' => $payload['category'], 'location' => $payload['location']]
    );

    header('Location: /data-centers?created=' . $id);
    exit;
  }

  public function edit(): void
  {
    AuthMiddleware::requireLogin();

    $id = (int)($_GET['id'] ?? 0);
    $dc = DataCenter::find($id);
    if (!$dc) {
      http_response_code(404);
      echo "Data Center not found";
      return;
    }

    View::render('data_centers/form.php', [
      'pageTitle' => 'Edit Data Center',
      'mode' => 'edit',
      'dc' => $dc,
    ]);
  }

  public function update(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $payload = $this->payloadFromPost();
    DataCenter::update($id, $payload);

    AuditService::log(
      $auth->userId(),
      'data_center.update',
      'Updated data center',
      'data_center',
      $id,
      ['name' => $payload['name'], 'category' => $payload['category'], 'location' => $payload['location']]
    );

    header('Location: /data-centers?updated=' . $id);
    exit;
  }

  public function delete(): void
  {
    AuthMiddleware::requireLogin();
    $auth = new AuthService();

    $id = (int)($_GET['id'] ?? 0);
    $old = DataCenter::find($id);
    DataCenter::delete($id);

    AuditService::log(
      $auth->userId(),
      'data_center.delete',
      'Deleted data center',
      'data_center',
      $id,
      ['name' => $old['name'] ?? null]
    );

    header('Location: /data-centers?deleted=' . $id);
    exit;
  }

  private function payloadFromPost(): array
  {
    $toNull = fn($v) => ($v === '' || $v === null) ? null : $v;

    $category = (string)($_POST['category'] ?? 'Primary');
    $allowed = ['Primary','Secondary','Disaster recovery','edge','colocation','cloud','hybrid'];
    if (!in_array($category, $allowed, true)) $category = 'Primary';

    return [
      'name' => trim((string)($_POST['name'] ?? '')),
      'category' => $category,
      'location' => $toNull(trim((string)($_POST['location'] ?? ''))),
      'description' => $toNull(trim((string)($_POST['description'] ?? ''))),
    ];
  }
}
