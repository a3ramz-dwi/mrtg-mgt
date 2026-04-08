ALTER TABLE devices
  ADD COLUMN snmp_ack_at DATETIME NULL DEFAULT NULL AFTER snmp_last_error,
  ADD COLUMN snmp_ack_by INT NULL DEFAULT NULL AFTER snmp_ack_at,
  ADD COLUMN snmp_ack_note VARCHAR(255) NULL DEFAULT NULL AFTER snmp_ack_by;

CREATE INDEX idx_devices_snmp_ack_at ON devices (snmp_ack_at);
