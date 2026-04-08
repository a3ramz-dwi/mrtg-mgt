ALTER TABLE device_interfaces
  ADD COLUMN mrtg_target_key VARCHAR(190) NULL AFTER is_mrtg_enabled,
  ADD COLUMN mrtg_cfg_file VARCHAR(255) NULL AFTER mrtg_target_key,
  ADD COLUMN mrtg_workdir VARCHAR(255) NULL AFTER mrtg_cfg_file;

CREATE INDEX idx_iface_mrtg_enabled ON device_interfaces(device_id, is_mrtg_enabled);
