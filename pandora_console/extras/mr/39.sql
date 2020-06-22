START TRANSACTION;


ALTER TABLE `tservice_element` ADD COLUMN `rules` text;
ALTER TABLE `tservice` ADD COLUMN `unknown_as_critical` tinyint(1) NOT NULL default 0 AFTER `warning`;

UPDATE `tservice` SET `auto_calculate`=0;

ALTER TABLE `tmensajes` ADD COLUMN `hidden_sent` TINYINT(1) UNSIGNED DEFAULT 0;

COMMIT;
