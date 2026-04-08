<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Models\AuditLog;
use App\Models\Device;
use App\Support\View;

final class EventTimelineController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);

    $q = trim((string)($_GET['q'] ?? ''));
    $eventType = trim((string)($_GET['event_type'] ?? ''));
    $deviceId = isset($_GET['device_id']) && (string)$_GET['device_id'] !== '' ? (int)$_GET['device_id'] : null;

    $res = AuditLog::paginate($page, $perPage, $q, $eventType, $deviceId);

    View::render('event_timeline/index.php', [
      'pageTitle' => 'Event Timeline',
      'rows' => $res['rows'],
      'total' => $res['total'],
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
      'eventType' => $eventType,
      'deviceId' => $deviceId,
      'devices' => Device::paginate(1, 500, '')['rows'],
      'eventTypes' => AuditLog::distinctEventTypes(),
    ]);
  }
}
