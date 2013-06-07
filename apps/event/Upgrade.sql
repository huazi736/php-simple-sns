/*个人活动*/
ALTER TABLE `del_event` ADD COLUMN `area` char(9) NULL DEFAULT NULL AFTER `addtime`;
ALTER TABLE `del_event` DROP COLUMN `city`;
ALTER TABLE `del_event` DROP COLUMN `street`;
ALTER TABLE `event` ADD COLUMN `area` char(9) NULL DEFAULT NULL AFTER `addtime`;
ALTER TABLE `event` DROP COLUMN `city`;
ALTER TABLE `event` DROP COLUMN `street`;
ALTER TABLE `event_messages` ADD COLUMN `group` varchar(50) NULL DEFAULT NULL AFTER `type`;
ALTER TABLE `event_messages` ADD COLUMN `filename` varchar(100) NULL DEFAULT NULL AFTER `group`;
ALTER TABLE `event_messages` DROP COLUMN `src`;
ALTER TABLE `event_users` MODIFY COLUMN `answer` tinyint(4) NOT NULL AFTER `type`;