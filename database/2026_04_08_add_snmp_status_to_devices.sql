ALTER TABLE devices
  ADD COLUMN snmp_last_ok TINYINT(1) NULL DEFAULT NULL AFTER is_mrtg_enabled,
  ADD COLUMN snmp_last_checked_at DATETIME NULL DEFAULT NULL AFTER snmp_last_ok,
  ADD COLUMN snmp_last_error VARCHAR(500) NULL DEFAULT NULL AFTER snmp_last_checked_at;

CREATE INDEX idx_devices_snmp_last_checked_at ON devices (snmp_last_checked_at);
CREATE INDEX idx_devices_snmp_last_ok ON devices (snmp_last_ok);
