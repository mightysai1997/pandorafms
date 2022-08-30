START TRANSACTION;

SET @id_config := (SELECT id_config FROM tconfig WHERE `token` = 'metaconsole_node_id' AND `value` IS NOT NULL ORDER BY id_config DESC LIMIT 1);
DELETE FROM tconfig WHERE `token` = 'metaconsole_node_id' AND (id_config < @id_config OR `value` IS NULL);

COMMIT;