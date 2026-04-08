<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use App\Support\DB;
use App\Support\View;

final class HistoryController
{
  public function index(): void
  {
    AuthMiddleware::requireLogin();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = (int)($_ENV['PAGINATION_PER_PAGE'] ?? 20);
    $offset = max(0, ($page - 1) * $perPage);

    $q = trim((string)($_GET['q'] ?? ''));

    $params = [];
    $where = '';
    if ($q !== '') {
      $where = "WHERE a.event_type LIKE ? OR a.message LIKE ? OR a.entity_type LIKE ? OR a.entity_id LIKE ? OR u.username LIKE ?";
      $like = '%' . $q . '%';
      $params = [$like,$like,$like,$like,$like];
    }

    $pdo = DB::pdo();
    $countSt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id $where");
    $countSt->execute($params);
    $total = (int)$countSt->fetchColumn();

    $sql = "
      SELECT a.*, u.username
      FROM audit_logs a
      LEFT JOIN users u ON u.id = a.user_id
      $where
      ORDER BY a.id DESC
      LIMIT $perPage OFFSET $offset
    ";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    View::render('history/index.php', [
      'pageTitle' => 'History (Audit Trail)',
      'rows' => $rows,
      'total' => $total,
      'page' => $page,
      'perPage' => $perPage,
      'q' => $q,
    ]);
  }
}
