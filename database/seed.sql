INSERT INTO roles (name, description) VALUES
('Admin','Full system access'),
('NOC Operator','Monitor + manage devices, diagnostics'),
('Support','Read-only monitoring'),
('Customer','Restricted portal')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Permissions minimal (akan kita tambah saat modul bertambah)
INSERT INTO permissions (name, description) VALUES
('auth.login','Can login'),
('devices.read','View devices'),
('devices.write','Manage devices'),
('interfaces.read','View interfaces'),
('interfaces.write','Manage interfaces'),
('mrtg.read','View MRTG graphs')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Grant Admin all permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name='Admin';

-- NOC Operator: read + write devices/interfaces + mrtg
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='NOC Operator' AND p.name IN ('auth.login','devices.read','devices.write','interfaces.read','interfaces.write','mrtg.read');

-- Support: read only + mrtg
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='Support' AND p.name IN ('auth.login','devices.read','interfaces.read','mrtg.read');

-- Customer: mrtg only (nanti dibatasi per-resource di Tahap 2)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='Customer' AND p.name IN ('auth.login','mrtg.read');

-- Create default admin (password: Admin123! -> HARUS diganti)
-- username: admin
-- password: Bismillah411Berkah
INSERT INTO users (username, password_hash, full_name, email, is_active)
VALUES (
  'admin',
  '$2a$12$mZ3qHlGD4AgoFVHQUrz8Hepe/M4df6t9gcfSnhXHM1hC5igJH.Ovq',
  'System Administrator',
  'admin@example.local',
  1
)
ON DUPLICATE KEY UPDATE
  full_name=VALUES(full_name),
  password_hash=VALUES(password_hash);

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r
WHERE u.username='admin' AND r.name='Admin';
