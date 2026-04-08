<?php
declare(strict_types=1);

namespace App\Services\Audit;

use App\Support\DB;

final class AuditService
{
  public static function log(
    ?int $userId,
    string $eventType,
    string $message,
    ?string $entityType = null,
    ?int $entityId = null,
    ?array $context = []
  ): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $ctx = $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : null;

    $st = DB::pdo()->prepare("
      INSERT INTO audit_logs
        (user_id, event_type, entity_type, entity_id, message, ip_address, user_agent, context_json)
      VALUES
        (:user_id, :event_type, :entity_type, :entity_id, :message, :ip_address, :user_agent, :context_json)
    ");
    $st->execute([
      'user_id' => $userId,
      'event_type' => $eventType,
      'entity_type' => $entityType,
      'entity_id' => $entityId !== null ? (string)$entityId : null,
      'message' => $message,
      'ip_address' => $ip,
      'user_agent' => $ua ? substr($ua, 0, 255) : null,
      'context_json' => $ctx,
    ]);
  }
}
