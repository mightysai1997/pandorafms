START TRANSACTION;

ALTER TABLE `tagent_custom_fields` MODIFY COLUMN `combo_values` TEXT NOT NULL DEFAULT '';


COMMIT;