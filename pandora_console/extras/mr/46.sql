START TRANSACTION;

ALTER TABLE `tagent_custom_fields` MODIFY COLUMN `combo_values` TEXT NOT NULL DEFAULT '';
ALTER TABLE `treport_content` MODIFY `external_source` MEDIUMTEXT;
ALTER TABLE `treport_content_template` MODIFY `external_source` MEDIUMTEXT;

UPDATE `talert_commands` SET `fields_descriptions` = '[\"Event&#x20;name\",\"Event&#x20;type\",\"Source\",\"Agent&#x20;name&#x20;or&#x20;_agent_\",\"Event&#x20;severity\",\"ID&#x20;extra\",\"Tags&#x20;separated&#x20;by&#x20;commas\",\"Comments\",\"\",\"\"]' WHERE `name` = "Monitoring&#x20;Event";

COMMIT;
