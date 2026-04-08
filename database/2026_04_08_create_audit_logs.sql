CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT NULL,
  event_type VARCHAR(80) NOT NULL,
  message VARCHAR(255) NOT NULL,
  entity_type VARCHAR(40) NULL,
  entity_id BIGINT NULL,
  context_json TEXT NULL,
  PRIMARY KEY (id),
  KEY idx_audit_created_at (created_at),
  KEY idx_audit_event_type (event_type),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
