START TRANSACTION;

ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_from` VARCHAR(100) DEFAULT '';
ALTER TABLE `tplanned_downtime` ADD COLUMN `cron_interval_to` VARCHAR(100) DEFAULT '';

ALTER TABLE `tusuario` ADD COLUMN `allowed_ip_active` TINYINT DEFAULT 0;
ALTER TABLE `tusuario` ADD COLUMN `allowed_ip_list` TEXT;

SET @id_config := (SELECT id_config FROM tconfig WHERE `token` = 'metaconsole_node_id' AND `value` IS NOT NULL ORDER BY id_config DESC LIMIT 1);
DELETE FROM tconfig WHERE `token` = 'metaconsole_node_id' AND (id_config < @id_config OR `value` IS NULL);

COMMIT;