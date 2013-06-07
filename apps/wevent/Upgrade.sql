/*ÍøÒ³»î¶¯*/
ALTER TABLE `del_web_event` ADD COLUMN `area` char(9) NULL DEFAULT NULL AFTER `addtime`;
ALTER TABLE `del_web_event` DROP COLUMN `city`;
ALTER TABLE `del_web_event` DROP COLUMN `street`;
ALTER TABLE `del_web_event_messages` ADD COLUMN `group` varchar(50) NULL DEFAULT NULL AFTER `type`;
ALTER TABLE `del_web_event_messages` ADD COLUMN `filename` varchar(100) NULL DEFAULT NULL AFTER `group`;
ALTER TABLE `del_web_event_messages` DROP COLUMN `src`;
ALTER TABLE `web_event` ADD COLUMN `area` char(9) NULL DEFAULT NULL AFTER `addtime`;
ALTER TABLE `web_event` DROP COLUMN `city`;
ALTER TABLE `web_event` DROP COLUMN `street`;
ALTER TABLE `web_event_messages` ADD COLUMN `group` varchar(50) NULL DEFAULT NULL AFTER `type`;
ALTER TABLE `web_event_messages` ADD COLUMN `filename` varchar(100) NULL DEFAULT NULL AFTER `group`;
ALTER TABLE `web_event_messages` DROP COLUMN `src`;