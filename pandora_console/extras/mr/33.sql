START TRANSACTION;

ALTER TABLE `tlayout_template_data` ADD COLUMN `cache_expiration` INTEGER UNSIGNED NOT NULL DEFAULT 0;

INSERT INTO `ttipo_modulo` VALUES
(34,'remote_cmd', 10, 'Remote execution, numeric data', 'mod_remote_cmd.png'),
(35,'remote_cmd_proc', 10, 'Remote execution, boolean data', 'mod_remote_cmd_proc.png'),
(36,'remote_cmd_string', 10, 'Remote execution, alphanumeric data', 'mod_remote_cmd_string.png'),
(37,'remote_cmd_inc', 10, 'Remote execution, incremental data', 'mod_remote_cmd_inc.png');

ALTER TABLE `tevent_rule` MODIFY COLUMN `event_type` enum('','unknown','alert_fired','alert_recovered','alert_ceased','alert_manual_validation','recon_host_detected','system','error','new_agent','going_up_warning','going_up_critical','going_down_warning','going_down_normal','going_down_critical','going_up_normal') default '';
ALTER TABLE `tevent_rule` MODIFY COLUMN `criticity` int(4) unsigned DEFAULT NULL;
ALTER TABLE `tevent_rule` MODIFY COLUMN `id_grupo` mediumint(4) DEFAULT NULL,


ALTER TABLE `tevent_rule` ADD COLUMN `log_content` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `log_source` TEXT;
ALTER TABLE `tevent_rule` ADD COLUMN `log_agent` TEXT;

ALTER TABLE `tevent_rule` ADD COLUMN `operator_agent` text COMMENT 'Operator for agent';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_usuario` text COMMENT 'Operator for id_usuario';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_grupo` text COMMENT 'Operator for id_grupo';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_evento` text COMMENT 'Operator for evento';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_event_type` text COMMENT 'Operator for event_type';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_module` text COMMENT 'Operator for module';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_alert` text COMMENT 'Operator for alert';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_criticity` text COMMENT 'Operator for criticity';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_user_comment` text COMMENT 'Operator for user_comment';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_id_tag` text COMMENT 'Operator for id_tag';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_content` text COMMENT 'Operator for log_content';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_source` text COMMENT 'Operator for log_source';
ALTER TABLE `tevent_rule` ADD COLUMN `operator_log_agent` text COMMENT 'Operator for log_agent';

ALTER TABLE `tevent_alert` ADD COLUMN `special_days` tinyint(1) default 0;
ALTER TABLE `tevent_alert` MODIFY COLUMN `time_threshold` int(10) NOT NULL default 86400;

CREATE TABLE `tremote_command` (
  `id` SERIAL,
  `name` varchar(150) NOT NULL,
  `timeout` int(10) unsigned NOT NULL default 30,
  `retries` int(10) unsigned NOT NULL default 3,
  `preconditions` text,
  `script` text,
  `postconditions` text,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `id_group` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `tremote_command_target` (
  `id` SERIAL,
  `rcmd_id` bigint unsigned NOT NULL,
  `id_agent` int(10) unsigned NOT NULL,
  `utimestamp` int(20) unsigned NOT NULL default 0,
  `stdout` MEDIUMTEXT,
  `stderr` MEDIUMTEXT,
  `errorlevel` int(10) unsigned NOT NULL default 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`rcmd_id`) REFERENCES `tremote_command`(`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `tconfig`(`token`, `value`) VALUES ('welcome_state', -1);

ALTER TABLE `tcredential_store` MODIFY COLUMN `product` enum('CUSTOM', 'AWS', 'AZURE', 'GOOGLE', 'SAP') default 'CUSTOM';

COMMIT;
