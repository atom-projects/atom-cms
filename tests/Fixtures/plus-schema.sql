/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_name` varchar(64) NOT NULL DEFAULT 'ACH_',
  `category` varchar(25) NOT NULL DEFAULT 'identity',
  `level` int(11) NOT NULL DEFAULT 1,
  `reward_pixels` int(11) NOT NULL DEFAULT 5,
  `reward_points` int(11) NOT NULL DEFAULT 5,
  `progress_needed` int(11) NOT NULL DEFAULT 1,
  `game_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acl_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `acl_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  `target_type` varchar(32) NOT NULL,
  `target_id` int(11) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`id`),
  KEY `target` (`target_type`,`target_id`),
  KEY `actor_created` (`actor_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `acl_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `acl_permissions` (
  `key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `category` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `is_orphan` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ambassador_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ambassador_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target` varchar(50) NOT NULL DEFAULT '',
  `sanctions_type` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `badge_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `badge_definitions` (
  `code` varchar(35) NOT NULL,
  `required_right` varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bantype` enum('user','ip','machine') NOT NULL DEFAULT 'user',
  `value` varchar(50) NOT NULL,
  `reason` text NOT NULL,
  `expire` datetime(6) DEFAULT NULL,
  `added_by` varchar(50) NOT NULL,
  `added_date` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `type_value_expire` (`bantype`,`value`,`expire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) NOT NULL DEFAULT 0,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `ai_type` enum('generic','bartender','pet') NOT NULL DEFAULT 'generic',
  `name` varchar(100) NOT NULL,
  `motto` varchar(120) NOT NULL,
  `look` text NOT NULL,
  `x` int(11) NOT NULL DEFAULT 0,
  `y` int(11) NOT NULL DEFAULT 0,
  `z` int(11) NOT NULL DEFAULT 0,
  `rotation` int(11) NOT NULL DEFAULT 0,
  `walk_mode` enum('stand','freeroam','specified_range') NOT NULL DEFAULT 'freeroam',
  `gender` varchar(5) NOT NULL DEFAULT 'M',
  `automatic_chat` enum('false','true') NOT NULL DEFAULT 'false',
  `speaking_interval` int(8) NOT NULL DEFAULT 30,
  `mix_sentences` tinyint(1) NOT NULL DEFAULT 0,
  `chat_bubble` int(11) NOT NULL DEFAULT 2,
  `room_ref` int(10) GENERATED ALWAYS AS (nullif(`room_id`,0)) STORED,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  KEY `ai_type` (`ai_type`),
  KEY `fk_bots_room_ref` (`room_ref`),
  CONSTRAINT `fk_bots_room_ref` FOREIGN KEY (`room_ref`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_bots_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bots_pet_commands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_pet_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `input` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bots_pet_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_pet_responses` (
  `pet_id` varchar(255) NOT NULL,
  `responses` text NOT NULL,
  PRIMARY KEY (`pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bots_petdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_petdata` (
  `id` int(11) unsigned NOT NULL,
  `type` int(11) unsigned DEFAULT 0,
  `race` varchar(11) DEFAULT NULL,
  `color` varchar(11) DEFAULT NULL,
  `energy` int(11) DEFAULT 0,
  `experience` int(11) DEFAULT 0,
  `nutrition` int(11) DEFAULT 0,
  `respect` int(11) DEFAULT 0,
  `createstamp` datetime(6) DEFAULT NULL,
  `have_saddle` int(11) DEFAULT 0,
  `hairdye` int(11) DEFAULT 1,
  `pethair` int(11) DEFAULT -1,
  `anyone_ride` int(11) DEFAULT 0,
  `gnome_clothing` varchar(85) DEFAULT '-1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_bots_petdata_id` FOREIGN KEY (`id`) REFERENCES `bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bots_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_responses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bot_ai` enum('generic','bartender') NOT NULL DEFAULT 'generic',
  `chat_keywords` text NOT NULL,
  `response_text` varchar(200) NOT NULL DEFAULT '',
  `response_mode` enum('say','shout','whisper') NOT NULL DEFAULT 'say',
  `response_beverage` varchar(25) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `bot_id` (`bot_ai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bots_speech`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_speech` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_id` int(10) unsigned NOT NULL,
  `text` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `bot_id` (`bot_id`),
  CONSTRAINT `fk_bots_speech_bot_id` FOREIGN KEY (`bot_id`) REFERENCES `bots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `camera_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_accounts` (
  `user_id` int(11) NOT NULL,
  `last_publish_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_camera_accounts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `camera_competition_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_competition_entries` (
  `media_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`media_id`),
  KEY `owner_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_camera_competition_entries_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `camera_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_media` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_camera_media_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `camera_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_publications` (
  `media_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`media_id`),
  KEY `owner_created` (`user_id`,`created_at`),
  CONSTRAINT `fk_camera_publications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `camera_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_purchases` (
  `item_id` int(10) unsigned NOT NULL,
  `media_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `media_id` (`media_id`),
  KEY `fk_camera_purchases_user_id` (`user_id`),
  CONSTRAINT `fk_camera_purchases_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_camera_purchases_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `camera_quota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_quota` (
  `user_id` int(11) NOT NULL,
  `quota_date` date NOT NULL,
  `captures` int(10) unsigned NOT NULL DEFAULT 0,
  `edits` int(10) unsigned NOT NULL DEFAULT 0,
  `last_capture_at` datetime(6) DEFAULT NULL,
  `last_edit_at` datetime(6) DEFAULT NULL,
  `last_thumbnail_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`quota_date`),
  KEY `quota_date` (`quota_date`),
  CONSTRAINT `fk_camera_quota_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_calendar_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_calendar_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `custom_image` varchar(255) NOT NULL DEFAULT '',
  `credits` int(11) NOT NULL DEFAULT 0,
  `duckets` int(11) NOT NULL DEFAULT 0,
  `diamonds` int(11) NOT NULL DEFAULT 0,
  `badge` varchar(50) NOT NULL DEFAULT '',
  `item_id` int(10) unsigned NOT NULL DEFAULT 0,
  `hc_days` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `campaign_calendar_rewards_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaign_calendars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_calendars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `image` varchar(255) NOT NULL DEFAULT '',
  `starts_at` datetime(6) NOT NULL,
  `days` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `lock_expired` tinyint(1) NOT NULL DEFAULT 1,
  `hc_duckets_multiplier` double NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_admin_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `action` varchar(32) NOT NULL,
  `entity_type` enum('PAGE','OFFER') NOT NULL,
  `catalog_type` enum('NORMAL') NOT NULL,
  `entity_id` int(11) NOT NULL,
  `operation` varchar(16) NOT NULL,
  `summary` varchar(255) NOT NULL DEFAULT '',
  `before_json` mediumtext DEFAULT NULL,
  `after_json` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_catalog_admin_log_entity` (`entity_type`,`entity_id`),
  KEY `idx_catalog_admin_log_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_bot_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_bot_presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `figure` varchar(255) NOT NULL,
  `gender` varchar(255) NOT NULL,
  `motto` varchar(255) NOT NULL,
  `ai_type` enum('pet','generic','bartender') NOT NULL DEFAULT 'generic',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_club_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_club_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `name` varchar(64) NOT NULL,
  `days` int(11) NOT NULL,
  `credits` int(11) NOT NULL DEFAULT 0,
  `points` int(11) NOT NULL DEFAULT 0,
  `points_type` int(11) NOT NULL DEFAULT 0,
  `giftable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_marketplace_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_marketplace_data` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `sprite` int(7) NOT NULL,
  `sold` int(7) NOT NULL DEFAULT 0,
  `avgprice` int(9) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sprite` (`sprite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_marketplace_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_marketplace_offers` (
  `offer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `asking_price` int(11) NOT NULL,
  `total_price` int(11) NOT NULL DEFAULT 0,
  `public_name` text NOT NULL,
  `sprite_id` int(11) NOT NULL,
  `item_type` enum('1','2') NOT NULL DEFAULT '1',
  `listed_at` datetime(6) DEFAULT NULL,
  `state` enum('1','2') NOT NULL DEFAULT '1',
  `extra_data` text NOT NULL,
  `furni_id` int(10) unsigned NOT NULL,
  `limited_number` int(11) NOT NULL DEFAULT 0,
  `limited_stack` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`offer_id`),
  KEY `user_state` (`user_id`,`state`),
  KEY `state_listed` (`state`,`listed_at`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `fk_catalog_marketplace_offers_item_id` FOREIGN KEY (`item_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_catalog_marketplace_offers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_offer_limited`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_offer_limited` (
  `offer_id` int(11) NOT NULL,
  `stack` int(10) unsigned NOT NULL,
  `sold` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `fk_catalog_offer_limited_offer` FOREIGN KEY (`offer_id`) REFERENCES `catalog_offers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ck_catalog_offer_limited_stock` CHECK (`stack` > 0 and `sold` <= `stack`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_offer_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_offer_products` (
  `offer_id` int(11) NOT NULL,
  `position` tinyint(3) unsigned NOT NULL,
  `product_type` enum('furni','effect','badge','bot','pet','habbicon') NOT NULL,
  `furniture_id` int(10) unsigned DEFAULT NULL,
  `effect_id` int(11) DEFAULT NULL,
  `badge_code` varchar(35) DEFAULT NULL,
  `bot_preset_id` int(11) DEFAULT NULL,
  `pet_type` int(11) DEFAULT NULL,
  `habbicon_id` int(11) DEFAULT NULL,
  `amount` int(10) unsigned NOT NULL DEFAULT 1,
  `extra_param` varchar(1024) NOT NULL DEFAULT '',
  PRIMARY KEY (`offer_id`,`position`),
  KEY `furniture_id` (`furniture_id`),
  KEY `fk_catalog_offer_products_badge` (`badge_code`),
  KEY `fk_catalog_offer_products_bot` (`bot_preset_id`),
  KEY `fk_catalog_offer_products_habbicon` (`habbicon_id`),
  CONSTRAINT `fk_catalog_offer_products_badge` FOREIGN KEY (`badge_code`) REFERENCES `badge_definitions` (`code`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_catalog_offer_products_bot` FOREIGN KEY (`bot_preset_id`) REFERENCES `catalog_bot_presets` (`id`),
  CONSTRAINT `fk_catalog_offer_products_furniture` FOREIGN KEY (`furniture_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_catalog_offer_products_habbicon` FOREIGN KEY (`habbicon_id`) REFERENCES `habbicons` (`id`),
  CONSTRAINT `fk_catalog_offer_products_offer` FOREIGN KEY (`offer_id`) REFERENCES `catalog_offers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ck_catalog_offer_products_amount` CHECK (`amount` between 1 and 1000),
  CONSTRAINT `ck_catalog_offer_products_target` CHECK (`product_type` = 'furni' = (`furniture_id` is not null) and `product_type` = 'effect' = (`effect_id` is not null) and `product_type` = 'badge' = (`badge_code` is not null) and `product_type` = 'bot' = (`bot_preset_id` is not null) and `product_type` = 'pet' = (`pet_type` is not null) and `product_type` = 'habbicon' = (`habbicon_id` is not null))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `localization_key` varchar(100) NOT NULL,
  `cost_credits` int(10) unsigned NOT NULL DEFAULT 0,
  `cost_points` int(10) unsigned NOT NULL DEFAULT 0,
  `points_type` int(10) unsigned NOT NULL DEFAULT 0,
  `club_level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `bulk_purchase` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `preview_image` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_catalog_offers_club_level` CHECK (`club_level` <= 2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_page_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_page_images` (
  `page_id` int(11) NOT NULL,
  `slot` tinyint(3) unsigned NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`page_id`,`slot`),
  CONSTRAINT `fk_catalog_page_images_page` FOREIGN KEY (`page_id`) REFERENCES `catalog_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_page_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_page_offers` (
  `page_id` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`page_id`,`offer_id`),
  KEY `offer_id` (`offer_id`),
  KEY `page_order` (`page_id`,`position`),
  CONSTRAINT `fk_catalog_page_offers_offer` FOREIGN KEY (`offer_id`) REFERENCES `catalog_offers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_catalog_page_offers_page` FOREIGN KEY (`page_id`) REFERENCES `catalog_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_page_texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_page_texts` (
  `page_id` int(11) NOT NULL,
  `slot` tinyint(3) unsigned NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`page_id`,`slot`),
  CONSTRAINT `fk_catalog_page_texts_page` FOREIGN KEY (`page_id`) REFERENCES `catalog_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `caption` varchar(128) NOT NULL,
  `icon` int(11) NOT NULL DEFAULT 0,
  `required_permission` varchar(191) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `link` varchar(128) DEFAULT NULL,
  `layout` varchar(64) NOT NULL DEFAULT 'default_3x3',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `required_club_level` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`),
  KEY `tree` (`parent_id`,`position`,`id`),
  KEY `fk_catalog_pages_permission` (`required_permission`),
  CONSTRAINT `fk_catalog_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `catalog_pages` (`id`),
  CONSTRAINT `fk_catalog_pages_permission` FOREIGN KEY (`required_permission`) REFERENCES `acl_permissions` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_pet_races`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_pet_races` (
  `raceid` int(11) NOT NULL,
  `color1` int(11) NOT NULL,
  `color2` int(11) NOT NULL,
  `has1color` tinyint(1) DEFAULT NULL,
  `has2color` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`raceid`,`color1`,`color2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT '',
  `image` varchar(255) DEFAULT '',
  `page_link` varchar(128) DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  `item_type` tinyint(4) NOT NULL DEFAULT 0,
  `offer_id` int(11) NOT NULL DEFAULT -1,
  `product_code` varchar(128) NOT NULL DEFAULT '',
  `expires_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `catalog_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_vouchers` (
  `voucher` varchar(45) NOT NULL,
  `type` enum('credits','duckets') NOT NULL DEFAULT 'credits',
  `value` int(11) NOT NULL DEFAULT 100,
  `current_uses` int(11) NOT NULL DEFAULT 0,
  `max_uses` int(11) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`voucher`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chatlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_time` (`room_id`,`timestamp`),
  KEY `user_id` (`user_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chatlogs_console`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatlogs_console` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_id` int(11) unsigned NOT NULL,
  `to_id` int(11) unsigned NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `from_id` (`from_id`),
  KEY `to_id` (`to_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chatlogs_console_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatlogs_console_invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_credit_spending`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_credit_spending` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credits` int(11) NOT NULL,
  `spent_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`spent_at`),
  CONSTRAINT `fk_club_credit_spending_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_gift_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_gift_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `gift_number` int(11) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `claimed_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`gift_number`),
  CONSTRAINT `fk_club_gift_claims_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_gift_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_gift_offers` (
  `offer_id` int(11) NOT NULL,
  `days_required` int(11) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`offer_id`),
  CONSTRAINT `fk_club_gift_offers_offer` FOREIGN KEY (`offer_id`) REFERENCES `catalog_offers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_membership_intervals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_membership_intervals` (
  `user_id` int(11) NOT NULL,
  `started_at` datetime(6) NOT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`started_at`),
  CONSTRAINT `fk_club_membership_intervals_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `club_paydays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_paydays` (
  `user_id` int(11) NOT NULL,
  `payday` datetime(6) NOT NULL,
  `spent` int(11) NOT NULL,
  `streak_bonus` int(11) NOT NULL,
  `spending_bonus` int(11) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`,`payday`),
  CONSTRAINT `fk_club_paydays_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crafting_altars_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafting_altars_recipes` (
  `altar_item_id` int(10) unsigned NOT NULL,
  `recipe_id` int(11) NOT NULL,
  PRIMARY KEY (`altar_item_id`,`recipe_id`),
  KEY `recipe_id` (`recipe_id`),
  CONSTRAINT `fk_crafting_altars_recipes_altar_item_id` FOREIGN KEY (`altar_item_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_crafting_altars_recipes_recipe_id` FOREIGN KEY (`recipe_id`) REFERENCES `crafting_recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crafting_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafting_recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `product_code` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `reward_item_id` int(10) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `secret` tinyint(1) NOT NULL DEFAULT 0,
  `remaining` int(11) DEFAULT NULL,
  `achievement` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `fk_crafting_recipes_reward_item_id` (`reward_item_id`),
  CONSTRAINT `fk_crafting_recipes_reward_item_id` FOREIGN KEY (`reward_item_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`remaining` is null or `remaining` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crafting_recipes_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `crafting_recipes_ingredients` (
  `recipe_id` int(11) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `amount` int(11) NOT NULL,
  PRIMARY KEY (`recipe_id`,`item_id`),
  KEY `fk_crafting_recipes_ingredients_item_id` (`item_id`),
  CONSTRAINT `fk_crafting_recipes_ingredients_item_id` FOREIGN KEY (`item_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_crafting_recipes_ingredients_recipe_id` FOREIGN KEY (`recipe_id`) REFERENCES `crafting_recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`amount` between 1 and 50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `furni_editor_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `furni_editor_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `action` enum('update','delete','furnidata_update','furnidata_revert') NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  `classname` varchar(70) NOT NULL DEFAULT '',
  `entry_id` int(11) DEFAULT NULL,
  `entry_section` enum('roomitemtypes','wallitemtypes') DEFAULT NULL,
  `before_json` mediumtext DEFAULT NULL,
  `after_json` mediumtext DEFAULT NULL,
  `reverted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_furni_editor_log_item` (`item_id`,`action`,`reverted`),
  KEY `idx_furni_editor_log_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `furniture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `furniture` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(70) NOT NULL,
  `public_name` varchar(56) NOT NULL DEFAULT '',
  `type` enum('s','i','e','h','v','r','b','p') NOT NULL DEFAULT 's',
  `width` int(11) NOT NULL DEFAULT 1,
  `length` int(11) NOT NULL DEFAULT 1,
  `stack_height` double NOT NULL DEFAULT 0,
  `can_stack` tinyint(1) NOT NULL DEFAULT 1,
  `can_sit` tinyint(1) NOT NULL DEFAULT 0,
  `is_walkable` tinyint(1) NOT NULL DEFAULT 0,
  `sprite_id` int(11) NOT NULL DEFAULT 0,
  `allow_recycle` tinyint(1) NOT NULL DEFAULT 1,
  `allow_trade` tinyint(1) NOT NULL DEFAULT 1,
  `allow_marketplace_sell` tinyint(1) NOT NULL DEFAULT 1,
  `allow_gift` tinyint(1) NOT NULL DEFAULT 1,
  `allow_inventory_stack` tinyint(1) NOT NULL DEFAULT 1,
  `interaction_type` varchar(25) NOT NULL DEFAULT 'default',
  `behaviour_data` int(11) NOT NULL DEFAULT 0,
  `interaction_modes_count` int(11) NOT NULL DEFAULT 1,
  `vending_ids` varchar(255) NOT NULL DEFAULT '0',
  `height_adjustable` varchar(50) NOT NULL DEFAULT '0',
  `effect_id` int(3) NOT NULL DEFAULT 0,
  `wired_id` int(11) NOT NULL DEFAULT 0,
  `is_rare` tinyint(1) NOT NULL DEFAULT 0,
  `extra_rot` tinyint(1) NOT NULL DEFAULT 0,
  `has_furnidata` tinyint(1) NOT NULL DEFAULT 0,
  `revision` int(11) NOT NULL DEFAULT 0,
  `category` varchar(64) DEFAULT NULL,
  `default_dir` int(11) NOT NULL DEFAULT 0,
  `xdim` int(11) NOT NULL DEFAULT 1,
  `ydim` int(11) NOT NULL DEFAULT 1,
  `part_colors` varchar(1024) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(1024) DEFAULT NULL,
  `ad_url` varchar(512) DEFAULT NULL,
  `excluded_dynamic` tinyint(1) NOT NULL DEFAULT 0,
  `custom_params` varchar(1024) DEFAULT NULL,
  `special_type` int(11) NOT NULL DEFAULT 1,
  `can_stand_on` tinyint(1) NOT NULL DEFAULT 0,
  `can_sit_on` tinyint(1) NOT NULL DEFAULT 0,
  `can_lay_on` tinyint(1) NOT NULL DEFAULT 0,
  `can_put_stuff_on` tinyint(1) DEFAULT NULL,
  `height` double DEFAULT NULL,
  `furni_line` varchar(64) DEFAULT NULL,
  `environment` varchar(64) DEFAULT NULL,
  `rare` tinyint(1) NOT NULL DEFAULT 0,
  `tradeable` tinyint(1) DEFAULT NULL,
  `recyclable` tinyint(1) DEFAULT NULL,
  `furnidata_sprite_id` int(11) GENERATED ALWAYS AS (if(`has_furnidata`,`sprite_id`,NULL)) STORED,
  `furnidata_classname` varchar(70) GENERATED ALWAYS AS (if(`has_furnidata`,`item_name`,NULL)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `furnidata_sprite_id` (`type`,`furnidata_sprite_id`),
  UNIQUE KEY `furnidata_classname` (`furnidata_classname`),
  KEY `sprite_id` (`sprite_id`) USING BTREE,
  CONSTRAINT `furnidata_kind` CHECK (`has_furnidata` = 0 or `type` in ('s','i'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `games_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `games_config` (
  `id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(25) NOT NULL DEFAULT '',
  `colour_one` varchar(25) NOT NULL DEFAULT '',
  `colour_two` varchar(25) NOT NULL DEFAULT '',
  `resource_path` varchar(125) NOT NULL,
  `string_three` varchar(25) NOT NULL DEFAULT '',
  `game_swf` varchar(255) NOT NULL DEFAULT '',
  `game_assets` varchar(255) NOT NULL DEFAULT '',
  `game_server_host` varchar(25) NOT NULL DEFAULT '',
  `game_server_port` varchar(25) NOT NULL DEFAULT '',
  `game_enabled` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_forum_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_forum_messages` (
  `group_id` int(10) unsigned NOT NULL,
  `id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `message_index` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime(6) NOT NULL,
  `state` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `moderator_id` int(11) NOT NULL DEFAULT 0,
  `moderated_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`group_id`,`id`),
  UNIQUE KEY `thread_messages` (`thread_id`,`message_index`),
  KEY `author_messages` (`author_id`),
  KEY `group_id` (`group_id`,`thread_id`),
  CONSTRAINT `fk_group_forum_messages_author_id` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_forum_messages_ibfk_1` FOREIGN KEY (`group_id`, `thread_id`) REFERENCES `group_forum_threads` (`group_id`, `id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_forum_post_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_forum_post_limits` (
  `user_id` int(11) NOT NULL,
  `posted_at` datetime(6) NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_group_forum_post_limits_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_forum_read_markers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_forum_read_markers` (
  `group_id` int(10) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_message_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `fk_group_forum_read_markers_user_id` (`user_id`),
  CONSTRAINT `fk_group_forum_read_markers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_forum_read_markers_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `group_forums` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_forum_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_forum_threads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(10) unsigned NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `pinned` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL,
  `updated_at` datetime(6) NOT NULL,
  `message_count` int(11) NOT NULL DEFAULT 0,
  `last_message_id` int(11) NOT NULL DEFAULT 0,
  `state` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `moderator_id` int(11) NOT NULL DEFAULT 0,
  `moderated_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forum_thread_identity` (`group_id`,`id`),
  KEY `forum_threads` (`group_id`,`pinned`,`updated_at`),
  KEY `fk_group_forum_threads_author_id` (`author_id`),
  CONSTRAINT `fk_group_forum_threads_author_id` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `group_forum_threads_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `group_forums` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_forums` (
  `group_id` int(10) unsigned NOT NULL,
  `read_permission` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `post_permission` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `thread_permission` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `moderate_permission` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `message_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`),
  CONSTRAINT `group_forums_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_memberships` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_user` (`group_id`,`user_id`),
  KEY `userid` (`user_id`),
  CONSTRAINT `fk_group_memberships_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_group_memberships_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_requests` (
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `userid` (`user_id`),
  CONSTRAINT `fk_group_requests_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_group_requests_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `badge` varchar(50) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `created` datetime(6) DEFAULT NULL,
  `room_id` int(10) unsigned NOT NULL DEFAULT 0,
  `state` enum('0','1','2') NOT NULL DEFAULT '0',
  `colour1` int(11) NOT NULL DEFAULT 242424,
  `colour2` int(11) NOT NULL DEFAULT 242424,
  `admindeco` tinyint(1) NOT NULL DEFAULT 1,
  `forum_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `owner` (`owner_id`),
  KEY `forum_enabled` (`forum_enabled`),
  CONSTRAINT `fk_groups_owner_id` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `groups_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups_items` (
  `type` enum('base','symbol','color','color2','color3') NOT NULL,
  `id` int(255) NOT NULL,
  `firstvalue` varchar(255) NOT NULL,
  `secondvalue` varchar(2000) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `habbicon_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `habbicon_collections` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `reward_id` int(11) NOT NULL DEFAULT 0,
  `cost_credits` int(10) unsigned NOT NULL DEFAULT 0,
  `cost_points` int(10) unsigned NOT NULL DEFAULT 0,
  `points_type` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `habbicons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `habbicons` (
  `id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `cost_credits` int(10) unsigned NOT NULL DEFAULT 0,
  `cost_points` int(10) unsigned NOT NULL DEFAULT 0,
  `points_type` int(10) unsigned NOT NULL DEFAULT 0,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `default_owned` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `collection_id` (`collection_id`),
  CONSTRAINT `habbicons_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `habbicon_collections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `housekeeping_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `housekeeping_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime(6) DEFAULT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_name` varchar(125) NOT NULL DEFAULT '',
  `target_type` varchar(16) NOT NULL DEFAULT 'user',
  `target_id` int(11) NOT NULL DEFAULT 0,
  `target_label` varchar(255) NOT NULL DEFAULT '',
  `action` varchar(64) NOT NULL,
  `detail` varchar(500) NOT NULL DEFAULT '',
  `success` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `actor` (`actor_id`),
  KEY `timestamp_action` (`timestamp`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `housekeeping_online_peaks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `housekeeping_online_peaks` (
  `day` date NOT NULL,
  `peak` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `room_id` int(10) NOT NULL DEFAULT 0,
  `base_item` int(10) unsigned NOT NULL,
  `extra_data` text NOT NULL,
  `x` int(11) NOT NULL DEFAULT 0,
  `y` int(11) NOT NULL DEFAULT 0,
  `z` double NOT NULL DEFAULT 0,
  `rot` int(11) NOT NULL DEFAULT 0,
  `wall_pos` varchar(100) DEFAULT '',
  `limited_number` int(11) DEFAULT 0,
  `limited_stack` int(11) DEFAULT 0,
  `room_ref` int(10) GENERATED ALWAYS AS (nullif(`room_id`,0)) STORED,
  PRIMARY KEY (`id`),
  KEY `roomid` (`room_id`),
  KEY `user_room` (`user_id`,`room_id`),
  KEY `base_user` (`base_item`,`user_id`),
  KEY `fk_items_room_ref` (`room_ref`),
  CONSTRAINT `fk_items_base_item` FOREIGN KEY (`base_item`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_items_room_ref` FOREIGN KEY (`room_ref`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_items_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `fk_items_groups_group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_groups_id` FOREIGN KEY (`id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `items_youtube`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items_youtube` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `youtube_id` varchar(35) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(150) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_client_namechange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_client_namechange` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `new_name` varchar(50) NOT NULL DEFAULT '',
  `old_name` varchar(50) NOT NULL DEFAULT '',
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_client_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_client_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `data_string` text NOT NULL,
  `machine_id` varchar(75) NOT NULL DEFAULT '',
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_client_trade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_client_trade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `1id` int(11) DEFAULT 0,
  `2id` int(11) DEFAULT 0,
  `1items` text DEFAULT NULL,
  `2items` text DEFAULT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messenger_friendships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_friendships` (
  `user_one_id` int(11) NOT NULL,
  `user_two_id` int(11) NOT NULL,
  `relationship` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_one_id`,`user_two_id`),
  KEY `user_two_id` (`user_two_id`),
  CONSTRAINT `fk_messenger_friendships_user_one_id` FOREIGN KEY (`user_one_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messenger_friendships_user_two_id` FOREIGN KEY (`user_two_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messenger_offline_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_offline_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_id` int(11) NOT NULL DEFAULT 0,
  `from_id` int(11) NOT NULL DEFAULT 0,
  `message` varchar(255) NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `to_id` (`to_id`),
  KEY `fk_messenger_offline_messages_from_id` (`from_id`),
  CONSTRAINT `fk_messenger_offline_messages_from_id` FOREIGN KEY (`from_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messenger_offline_messages_to_id` FOREIGN KEY (`to_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messenger_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_requests` (
  `from_id` int(11) NOT NULL,
  `to_id` int(11) NOT NULL,
  PRIMARY KEY (`from_id`,`to_id`),
  KEY `to_id` (`to_id`),
  CONSTRAINT `fk_messenger_requests_from_id` FOREIGN KEY (`from_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messenger_requests_to_id` FOREIGN KEY (`to_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moderation_preset_action_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_preset_action_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moderation_preset_action_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_preset_action_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `caption` varchar(32) NOT NULL,
  `message_text` text NOT NULL,
  `mute_hours` int(11) NOT NULL DEFAULT 0,
  `ban_hours` int(11) NOT NULL DEFAULT 24,
  `ip_ban_hours` int(11) NOT NULL DEFAULT 0,
  `trade_lock_days` int(11) NOT NULL DEFAULT 0,
  `notice` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_moderation_preset_action_messages_parent_id` (`parent_id`),
  CONSTRAINT `fk_moderation_preset_action_messages_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `moderation_preset_action_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moderation_presets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_presets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('user','room') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `enabled` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moderation_topic_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_topic_actions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `caption` varchar(225) NOT NULL DEFAULT '',
  `message_text` varchar(255) NOT NULL,
  `default_sanction` varchar(255) NOT NULL,
  `mute_time` int(11) NOT NULL,
  `ban_time` int(11) NOT NULL,
  `ip_time` int(11) NOT NULL,
  `trade_lock_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_moderation_topic_actions_parent_id` (`parent_id`),
  CONSTRAINT `fk_moderation_topic_actions_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `moderation_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `moderation_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(225) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `navigator_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `navigator_categories` (
  `id` int(11) NOT NULL,
  `category` enum('official_view','hotel_view','myworld_view','roomads_view','query') NOT NULL DEFAULT 'hotel_view',
  `category_identifier` varchar(35) NOT NULL DEFAULT '',
  `public_name` varchar(35) NOT NULL DEFAULT '',
  `view_mode` enum('REGULAR','THUMBNAIL') NOT NULL DEFAULT 'REGULAR',
  `required_permission` varchar(191) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `category_type` varchar(25) NOT NULL DEFAULT 'category',
  `search_allowance` enum('NOTHING','SHOW_MORE') NOT NULL DEFAULT 'SHOW_MORE',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `order_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `navigator_publics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `navigator_publics` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT,
  `caption` varchar(64) NOT NULL,
  `description` varchar(150) NOT NULL,
  `image_url` text NOT NULL,
  `order_num` int(11) NOT NULL DEFAULT 1,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`room_id`),
  KEY `ordernum` (`order_num`),
  CONSTRAINT `fk_navigator_publics_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL DEFAULT '',
  `level_num` int(11) NOT NULL DEFAULT 0,
  `goal_type` int(10) NOT NULL DEFAULT 0,
  `goal_data` int(10) unsigned NOT NULL DEFAULT 0,
  `action` varchar(32) NOT NULL DEFAULT '',
  `pixel_reward` int(11) NOT NULL DEFAULT 10,
  `data_bit` varchar(2) NOT NULL DEFAULT '',
  `reward_type` enum('0','1','2','3','4','5') NOT NULL DEFAULT '0',
  `timestamp_unlock` datetime(6) DEFAULT NULL,
  `timestamp_lock` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rcon_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rcon_grants` (
  `idempotency_key` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `payload_sha256` char(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `status` varchar(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL DEFAULT 'applied',
  `result_json` mediumtext NOT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`idempotency_key`),
  KEY `idx_rcon_grants_user` (`user_id`),
  CONSTRAINT `fk_rcon_grants_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recycler_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recycler_levels` (
  `level` int(11) NOT NULL,
  `chance` int(11) NOT NULL,
  PRIMARY KEY (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recycler_prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recycler_prizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` int(11) NOT NULL,
  `item_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recycler_level_item` (`level`,`item_id`),
  KEY `fk_recycler_prizes_item_id` (`item_id`),
  CONSTRAINT `fk_recycler_prizes_item_id` FOREIGN KEY (`item_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_recycler_prizes_level` FOREIGN KEY (`level`) REFERENCES `recycler_levels` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recycler_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recycler_settings` (
  `id` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `slots` int(11) NOT NULL DEFAULT 5,
  `cooldown_seconds` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reward_track_prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_track_prizes` (
  `track_id` varchar(64) NOT NULL,
  `id` varchar(64) NOT NULL,
  `required_points` int(11) NOT NULL DEFAULT 0,
  `product_item_type_id` int(11) NOT NULL DEFAULT 0,
  `reward_type` varchar(32) NOT NULL DEFAULT 'duckets',
  `extra_params` varchar(255) NOT NULL DEFAULT '',
  `reward_amount` int(11) NOT NULL DEFAULT 0,
  `premium` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`track_id`,`id`),
  CONSTRAINT `fk_reward_track_prizes_track_id` FOREIGN KEY (`track_id`) REFERENCES `reward_tracks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reward_track_task_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_track_task_levels` (
  `track_id` varchar(64) NOT NULL,
  `task_id` varchar(64) NOT NULL,
  `level` int(11) NOT NULL,
  `required_count` int(11) NOT NULL DEFAULT 1,
  `points_reward` int(11) NOT NULL DEFAULT 0,
  `premium` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`track_id`,`task_id`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reward_track_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_track_tasks` (
  `track_id` varchar(64) NOT NULL,
  `id` varchar(64) NOT NULL,
  `action_type` varchar(64) NOT NULL,
  `parameter` varchar(255) NOT NULL DEFAULT '',
  `premium` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`track_id`,`id`),
  CONSTRAINT `fk_reward_track_tasks_track_id` FOREIGN KEY (`track_id`) REFERENCES `reward_tracks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reward_tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_tracks` (
  `id` varchar(64) NOT NULL,
  `theme` varchar(64) NOT NULL DEFAULT 'blue',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `starts_at` datetime(6) DEFAULT NULL,
  `ends_at` datetime(6) DEFAULT NULL,
  `has_premium` tinyint(1) NOT NULL DEFAULT 0,
  `premium_task_points_boost` double NOT NULL DEFAULT 0,
  `premium_instant_points` int(11) NOT NULL DEFAULT 0,
  `premium_cost_diamonds` int(11) NOT NULL DEFAULT 0,
  `premium_cost_credits` int(11) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_limits` (
  `role_id` int(11) NOT NULL,
  `limit_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`limit_key`),
  CONSTRAINT `role_limits_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`role_id`,`permission_key`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `weight` int(11) NOT NULL DEFAULT 0,
  `security_level` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `badge_code` varchar(64) NOT NULL DEFAULT '',
  `is_staff` tinyint(1) NOT NULL DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updated_at` datetime(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`security_level` <= 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_bans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_bans` (
  `user_id` int(11) NOT NULL DEFAULT 0,
  `room_id` int(10) NOT NULL DEFAULT 0,
  `expire` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`room_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_room_bans_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_bans_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_chat_styles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_chat_styles` (
  `id` int(11) NOT NULL,
  `name` varchar(25) DEFAULT '',
  `required_permission` varchar(191) DEFAULT '',
  `requires_hc` tinyint(1) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_filter` (
  `word` varchar(15) NOT NULL DEFAULT '',
  `room_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`room_id`,`word`),
  CONSTRAINT `fk_room_filter_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_items_moodlight`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_items_moodlight` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `current_preset` int(11) NOT NULL,
  `preset_one` text NOT NULL,
  `preset_two` text NOT NULL,
  `preset_three` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `fk_room_items_moodlight_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_items_tele_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_items_tele_links` (
  `tele_one_id` int(10) unsigned NOT NULL,
  `tele_two_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tele_one_id`),
  KEY `tele_two_id` (`tele_two_id`),
  CONSTRAINT `fk_room_items_tele_links_tele_one_id` FOREIGN KEY (`tele_one_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_items_tele_links_tele_two_id` FOREIGN KEY (`tele_two_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_items_toner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_items_toner` (
  `id` int(11) unsigned NOT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `data1` int(11) NOT NULL,
  `data2` int(11) NOT NULL,
  `data3` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_room_items_toner_id` FOREIGN KEY (`id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_models` (
  `id` varchar(100) NOT NULL,
  `door_x` int(11) NOT NULL,
  `door_y` int(11) NOT NULL,
  `door_z` double NOT NULL,
  `door_dir` int(4) NOT NULL DEFAULT 2,
  `heightmap` text NOT NULL,
  `public_items` text NOT NULL,
  `required_club_level` int(11) NOT NULL DEFAULT 0,
  `required_permission` varchar(191) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `custom` tinyint(1) NOT NULL DEFAULT 0,
  `wall_height` int(11) NOT NULL DEFAULT -1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_music_disc_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_music_disc_definitions` (
  `base_item` int(10) unsigned NOT NULL,
  `song_id` int(11) NOT NULL,
  PRIMARY KEY (`base_item`),
  KEY `song_id` (`song_id`),
  CONSTRAINT `fk_room_music_disc_definitions_base_item` FOREIGN KEY (`base_item`) REFERENCES `furniture` (`id`),
  CONSTRAINT `room_music_disc_definitions_ibfk_1` FOREIGN KEY (`song_id`) REFERENCES `room_music_songs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_music_players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_music_players` (
  `item_id` int(10) unsigned NOT NULL,
  `start_index` int(11) NOT NULL DEFAULT 0,
  `version` bigint(20) NOT NULL DEFAULT 0,
  `started_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  CONSTRAINT `room_music_players_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_music_playlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_music_playlist` (
  `player_id` int(10) unsigned NOT NULL,
  `disc_id` int(10) unsigned NOT NULL,
  `position` int(11) NOT NULL,
  `song_id` int(11) NOT NULL,
  PRIMARY KEY (`player_id`,`position`),
  UNIQUE KEY `disc_id` (`disc_id`),
  KEY `song_id` (`song_id`),
  CONSTRAINT `fk_room_music_playlist_disc_id` FOREIGN KEY (`disc_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_music_playlist_player_id` FOREIGN KEY (`player_id`) REFERENCES `room_music_players` (`item_id`) ON DELETE CASCADE,
  CONSTRAINT `room_music_playlist_ibfk_3` FOREIGN KEY (`song_id`) REFERENCES `room_music_songs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_music_songs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_music_songs` (
  `id` int(11) NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(255) NOT NULL,
  `creator` varchar(255) NOT NULL,
  `trax_data` mediumtext NOT NULL,
  `length_ms` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_poll_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_poll_questions` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `text` text NOT NULL,
  `category` int(11) NOT NULL DEFAULT 0,
  `answer_type` int(11) NOT NULL DEFAULT 0,
  `choices` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `poll_questions` (`poll_id`,`sort_order`),
  CONSTRAINT `fk_room_poll_questions_poll_id` FOREIGN KEY (`poll_id`) REFERENCES `room_polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_poll_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_poll_responses` (
  `poll_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `answers` longtext NOT NULL,
  `completed_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`poll_id`,`user_id`),
  KEY `fk_room_poll_responses_user_id` (`user_id`),
  CONSTRAINT `fk_room_poll_responses_poll_id` FOREIGN KEY (`poll_id`) REFERENCES `room_polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_poll_responses_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_polls` (
  `id` int(11) NOT NULL,
  `room_id` int(10) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(32) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `end_message` text NOT NULL,
  `nps` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_id` (`room_id`),
  CONSTRAINT `fk_room_polls_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_promotions` (
  `room_id` int(11) NOT NULL,
  `title` varchar(35) NOT NULL DEFAULT '',
  `description` varchar(220) NOT NULL DEFAULT '',
  `timestamp_start` datetime(6) DEFAULT NULL,
  `timestamp_expire` datetime(6) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`room_id`),
  CONSTRAINT `fk_room_promotions_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_rights` (
  `room_id` int(10) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`room_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_room_rights_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_room_rights_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `room_wired_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_wired_settings` (
  `room_id` int(10) NOT NULL,
  `inspect_mask` int(11) NOT NULL DEFAULT 2,
  `modify_mask` int(11) NOT NULL DEFAULT 2,
  `timezone` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`room_id`),
  CONSTRAINT `fk_room_wired_settings_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `roomtype` enum('public','private') NOT NULL DEFAULT 'private',
  `caption` varchar(100) NOT NULL DEFAULT 'Room',
  `owner` int(11) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `category` int(11) NOT NULL DEFAULT 0,
  `state` enum('open','locked','password','invisible') NOT NULL DEFAULT 'open',
  `users_now` int(11) NOT NULL DEFAULT 0,
  `users_max` int(11) NOT NULL DEFAULT 25,
  `model_name` varchar(50) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `tags` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(30) NOT NULL DEFAULT '',
  `wallpaper` varchar(10) NOT NULL DEFAULT '0.0',
  `floor` varchar(10) NOT NULL DEFAULT '0.0',
  `landscape` varchar(10) NOT NULL DEFAULT '0.0',
  `wallthick` int(1) NOT NULL DEFAULT 0,
  `floorthick` int(1) NOT NULL DEFAULT 0,
  `group_id` int(11) unsigned NOT NULL DEFAULT 0,
  `mute_settings` tinyint(1) NOT NULL DEFAULT 1,
  `ban_settings` tinyint(1) NOT NULL DEFAULT 1,
  `kick_settings` enum('0','1','2') NOT NULL DEFAULT '1',
  `chat_mode` int(11) NOT NULL DEFAULT 0,
  `chat_size` int(11) NOT NULL DEFAULT 0,
  `chat_speed` int(11) NOT NULL DEFAULT 0,
  `chat_extra_flood` int(11) NOT NULL DEFAULT 0,
  `chat_hearing_distance` int(11) NOT NULL DEFAULT 14,
  `trade_settings` int(11) NOT NULL DEFAULT 2,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `pull_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `enables_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `respect_notifications_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `pet_morphs_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `spull_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `spush_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sale_price` int(5) NOT NULL DEFAULT 0,
  `lay_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `allow_pets` tinyint(1) NOT NULL DEFAULT 0,
  `allow_pets_eat` tinyint(1) NOT NULL DEFAULT 0,
  `room_blocking_disabled` tinyint(1) NOT NULL DEFAULT 0,
  `allow_hidewall` tinyint(1) NOT NULL DEFAULT 0,
  `hide_wired` tinyint(1) NOT NULL DEFAULT 0,
  `group_ref` int(11) unsigned GENERATED ALWAYS AS (nullif(`group_id`,0)) STORED,
  PRIMARY KEY (`id`),
  KEY `users_now` (`users_now`),
  KEY `caption` (`caption`),
  KEY `group_id` (`group_id`),
  KEY `owner_caption` (`owner`,`caption`),
  KEY `fk_rooms_group_ref` (`group_ref`),
  KEY `fk_rooms_model_name` (`model_name`),
  KEY `fk_rooms_category` (`category`),
  CONSTRAINT `fk_rooms_category` FOREIGN KEY (`category`) REFERENCES `navigator_categories` (`id`),
  CONSTRAINT `fk_rooms_group_ref` FOREIGN KEY (`group_ref`) REFERENCES `groups` (`id`),
  CONSTRAINT `fk_rooms_model_name` FOREIGN KEY (`model_name`) REFERENCES `room_models` (`id`),
  CONSTRAINT `fk_rooms_owner` FOREIGN KEY (`owner`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `safety_quiz_questions` (
  `quiz_code` varchar(32) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_count` int(11) NOT NULL,
  `correct_answer` int(11) NOT NULL,
  PRIMARY KEY (`quiz_code`,`question_id`),
  CONSTRAINT `fk_safety_quiz_questions_quiz_code` FOREIGN KEY (`quiz_code`) REFERENCES `safety_quizzes` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `safety_quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `safety_quizzes` (
  `code` varchar(32) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `question_count` int(11) NOT NULL,
  `retry_seconds` int(11) NOT NULL DEFAULT 7200,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_landing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_landing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(35) DEFAULT '',
  `text` text DEFAULT NULL,
  `button_text` varchar(25) DEFAULT '',
  `button_type` enum('0','1','2','3') DEFAULT '0',
  `button_link` varchar(90) DEFAULT NULL,
  `image_link` varchar(120) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_locale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_locale` (
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_reward_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_reward_logs` (
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`reward_id`),
  KEY `fk_server_reward_logs_reward_id` (`reward_id`),
  CONSTRAINT `fk_server_reward_logs_reward_id` FOREIGN KEY (`reward_id`) REFERENCES `server_rewards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_server_reward_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_rewards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reward_start` datetime(6) DEFAULT NULL,
  `reward_end` datetime(6) DEFAULT NULL,
  `reward_type` enum('credits','badge','diamonds','duckets','none') NOT NULL DEFAULT 'none',
  `reward_data` varchar(255) NOT NULL,
  `message` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_settings` (
  `key` varchar(255) NOT NULL DEFAULT 'server.variable',
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `snowwar_game_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snowwar_game_tokens` (
  `user_id` int(11) NOT NULL,
  `games` int(11) NOT NULL DEFAULT 0,
  `free_games_date` date DEFAULT NULL,
  `free_games_used` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_snowwar_game_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `snowwar_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snowwar_scores` (
  `user_id` int(11) NOT NULL,
  `week_start` date NOT NULL,
  `score` bigint(20) NOT NULL DEFAULT 0,
  `matches` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`week_start`),
  KEY `week_score` (`week_start`,`score`),
  CONSTRAINT `fk_snowwar_scores_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `snowwar_token_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snowwar_token_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `localization_id` varchar(64) NOT NULL,
  `price_credits` int(11) NOT NULL DEFAULT 0,
  `price_points` int(11) NOT NULL DEFAULT 0,
  `points_type` int(11) NOT NULL DEFAULT 0,
  `games` int(11) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `order_num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `localization_id` (`localization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `talents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('citizenship','helper') NOT NULL,
  `level` int(11) DEFAULT 0,
  `data_actions` text NOT NULL,
  `data_gifts` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_level` (`type`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `talents_sub_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talents_sub_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `talent_type` enum('citizenship','helper') NOT NULL DEFAULT 'citizenship',
  `talent_level` int(11) NOT NULL DEFAULT 0,
  `sub_level` int(11) NOT NULL DEFAULT 0,
  `badge_code` varchar(45) NOT NULL DEFAULT '',
  `required_progress` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` char(32) DEFAULT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  `revoked_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_user_access_tokens_session_id` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_access_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_achievements` (
  `userid` int(11) NOT NULL,
  `group` varchar(255) NOT NULL,
  `level` int(11) NOT NULL,
  `progress` int(11) NOT NULL,
  PRIMARY KEY (`userid`,`group`),
  CONSTRAINT `fk_user_achievements_userid` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `badge_id` varchar(100) NOT NULL,
  `badge_slot` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id, badge_id` (`user_id`,`badge_id`),
  CONSTRAINT `fk_user_badges_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_calendar_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_calendar_claims` (
  `user_id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `claimed_at` datetime(6) NOT NULL,
  PRIMARY KEY (`user_id`,`campaign_id`,`day`),
  KEY `fk_user_calendar_claims_campaign_id` (`campaign_id`),
  KEY `fk_user_calendar_claims_reward_id` (`reward_id`),
  CONSTRAINT `fk_user_calendar_claims_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaign_calendars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_calendar_claims_reward_id` FOREIGN KEY (`reward_id`) REFERENCES `campaign_calendar_rewards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_calendar_claims_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_clothing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_clothing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `part_id` int(10) unsigned NOT NULL,
  `part` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_part` (`user_id`,`part_id`),
  CONSTRAINT `fk_user_clothing_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_club_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_club_memberships` (
  `user_id` int(11) NOT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  `started_at` datetime(6) DEFAULT NULL,
  `first_started_at` datetime(6) DEFAULT NULL,
  `past_seconds` bigint(20) NOT NULL DEFAULT 0,
  `modified_at` datetime(6) DEFAULT NULL,
  `gifts_claimed` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_club_memberships_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_crafting_recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_crafting_recipes` (
  `user_id` int(11) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`recipe_id`),
  KEY `recipe_id` (`recipe_id`),
  CONSTRAINT `fk_user_crafting_recipes_recipe_id` FOREIGN KEY (`recipe_id`) REFERENCES `crafting_recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_crafting_recipes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_currencies` (
  `user_id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`type`),
  KEY `type_amount` (`type`,`amount`),
  CONSTRAINT `fk_user_currencies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_user_currencies_type` CHECK (`type` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_effects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `effect_id` int(11) DEFAULT 1,
  `total_duration` int(11) DEFAULT 3600,
  `is_activated` tinyint(1) DEFAULT 0,
  `quantity` int(11) DEFAULT 0,
  `activated_stamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_user_effects_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_favorites` (
  `user_id` int(11) NOT NULL,
  `room_id` int(10) NOT NULL,
  PRIMARY KEY (`user_id`,`room_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_user_favorites_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_favorites_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_ignores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ignores` (
  `user_id` int(11) NOT NULL,
  `ignore_id` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`ignore_id`),
  KEY `ignore_id` (`ignore_id`),
  CONSTRAINT `fk_user_ignores_ignore_id` FOREIGN KEY (`ignore_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_ignores_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_info` (
  `user_id` int(11) NOT NULL,
  `bans` int(11) NOT NULL DEFAULT 0,
  `cautions` int(11) NOT NULL DEFAULT 0,
  `cfhs` int(11) NOT NULL DEFAULT 0,
  `cfhs_abusive` int(11) NOT NULL DEFAULT 0,
  `trading_locks_count` int(11) NOT NULL DEFAULT 0,
  `trading_locked` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_info_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permissions` (
  `user_id` int(11) NOT NULL,
  `permission_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effect` enum('grant','deny') NOT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `reason` varchar(512) NOT NULL DEFAULT '',
  `expires_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`user_id`,`permission_key`),
  KEY `expires_at` (`expires_at`),
  KEY `fk_user_permissions_granted_by` (`granted_by`),
  CONSTRAINT `fk_user_permissions_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_permissions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_presents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_presents` (
  `item_id` int(10) unsigned NOT NULL,
  `base_id` int(10) unsigned NOT NULL,
  `extra_data` text NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `fk_user_presents_base_id` (`base_id`),
  CONSTRAINT `fk_user_presents_base_id` FOREIGN KEY (`base_id`) REFERENCES `furniture` (`id`),
  CONSTRAINT `fk_user_presents_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_quests` (
  `user_id` int(11) NOT NULL,
  `quest_id` int(10) unsigned NOT NULL,
  `progress` int(10) DEFAULT 0,
  PRIMARY KEY (`user_id`,`quest_id`),
  KEY `fk_user_quests_quest_id` (`quest_id`),
  CONSTRAINT `fk_user_quests_quest_id` FOREIGN KEY (`quest_id`) REFERENCES `quests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_quests_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_recycler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_recycler` (
  `user_id` int(11) NOT NULL,
  `next_allowed_at` datetime(6) NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_recycler_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_remember_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `family_id` char(32) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  `used_at` datetime(6) DEFAULT NULL,
  `grace_uses` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `revoked_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  KEY `family_id` (`family_id`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_user_remember_tokens_family_id` FOREIGN KEY (`family_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_remember_tokens_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roles` (
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `granted_by` int(11) DEFAULT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  `created_at` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `expires_at` (`expires_at`),
  KEY `role_id` (`role_id`),
  KEY `fk_user_roles_granted_by` (`granted_by`),
  CONSTRAINT `fk_user_roles_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_roles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_roomvisits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roomvisits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(10) NOT NULL,
  `entry_timestamp` datetime(6) DEFAULT NULL,
  `exit_timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_room_entry` (`user_id`,`room_id`,`entry_timestamp`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `fk_user_roomvisits_room_id` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roomvisits_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_safety_quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_safety_quizzes` (
  `user_id` int(11) NOT NULL,
  `quiz_code` varchar(32) NOT NULL,
  `next_allowed_at` datetime(6) NOT NULL,
  `completed_at` datetime(6) DEFAULT NULL,
  `award_pending` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`quiz_code`),
  KEY `fk_user_safety_quizzes_quiz_code` (`quiz_code`),
  CONSTRAINT `fk_user_safety_quizzes_quiz_code` FOREIGN KEY (`quiz_code`) REFERENCES `safety_quizzes` (`code`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_safety_quizzes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_saved_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_saved_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `filter` varchar(65) NOT NULL DEFAULT '',
  `search_code` varchar(65) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  CONSTRAINT `fk_user_saved_searches_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` char(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `revoked_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_user_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_statistics` (
  `id` int(7) NOT NULL,
  `RoomVisits` int(7) NOT NULL DEFAULT 0,
  `OnlineTime` bigint(20) NOT NULL DEFAULT 0,
  `Respect` int(6) NOT NULL DEFAULT 0,
  `RespectGiven` int(6) NOT NULL DEFAULT 0,
  `GiftsGiven` int(6) NOT NULL DEFAULT 0,
  `GiftsReceived` int(6) NOT NULL DEFAULT 0,
  `DailyRespectPoints` int(1) NOT NULL DEFAULT 3,
  `DailyPetRespectPoints` int(1) NOT NULL DEFAULT 3,
  `AchievementScore` int(7) NOT NULL DEFAULT 0,
  `quest_id` int(10) unsigned NOT NULL DEFAULT 0,
  `quest_progress` int(10) NOT NULL DEFAULT 0,
  `groupid` int(11) unsigned NOT NULL DEFAULT 0,
  `respectsTimestamp` varchar(6) DEFAULT '10/19',
  `forum_posts` int(11) NOT NULL DEFAULT 0,
  `group_ref` int(11) unsigned GENERATED ALWAYS AS (nullif(`groupid`,0)) STORED,
  PRIMARY KEY (`id`),
  KEY `OnlineTime` (`OnlineTime`),
  KEY `Respect` (`Respect`),
  KEY `AchievementScore` (`AchievementScore`),
  KEY `fk_user_statistics_group_ref` (`group_ref`),
  CONSTRAINT `fk_user_statistics_group_ref` FOREIGN KEY (`group_ref`) REFERENCES `groups` (`id`),
  CONSTRAINT `fk_user_statistics_id` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_talent_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_talent_rewards` (
  `user_id` int(11) NOT NULL,
  `type` enum('citizenship','helper') NOT NULL,
  `level` int(11) NOT NULL,
  PRIMARY KEY (`user_id`,`type`,`level`),
  CONSTRAINT `fk_user_talent_rewards_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_vouchers` (
  `user_id` int(11) NOT NULL,
  `voucher` varchar(45) NOT NULL,
  PRIMARY KEY (`user_id`,`voucher`),
  KEY `fk_user_vouchers_voucher` (`voucher`),
  CONSTRAINT `fk_user_vouchers_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_vouchers_voucher` FOREIGN KEY (`voucher`) REFERENCES `catalog_vouchers` (`voucher`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_wardrobe`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_wardrobe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `slot_id` int(10) unsigned NOT NULL,
  `look` varchar(120) NOT NULL,
  `gender` enum('F','M') NOT NULL DEFAULT 'M',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `slot_id` (`slot_id`),
  CONSTRAINT `fk_user_wardrobe_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(125) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `mail` varchar(255) DEFAULT 'defaultuser@meth0d.org',
  `auth_ticket` varchar(60) DEFAULT NULL,
  `auth_ticket_expires_at` datetime(6) DEFAULT NULL,
  `auth_ticket_exchanged` tinyint(1) NOT NULL DEFAULT 0,
  `rank` int(1) unsigned DEFAULT 1,
  `credits` int(11) DEFAULT 50000,
  `look` char(255) DEFAULT NULL,
  `gender` enum('M','F') DEFAULT 'M',
  `motto` char(50) DEFAULT NULL,
  `online` tinyint(1) DEFAULT 0,
  `ip_last` varchar(45) DEFAULT '',
  `ip_reg` varchar(45) DEFAULT NULL,
  `vip` tinyint(1) DEFAULT 1,
  `time_muted` double DEFAULT 0,
  `bubble_id` tinyint(4) NOT NULL DEFAULT 0,
  `credential_generation` int(11) unsigned NOT NULL DEFAULT 0,
  `auth_ticket_session` char(32) DEFAULT NULL,
  `account_created` datetime DEFAULT NULL,
  `last_online` datetime DEFAULT NULL,
  `last_change` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `ip_last` (`ip_last`),
  KEY `online` (`online`),
  KEY `mail` (`mail`),
  KEY `auth_ticket` (`auth_ticket`),
  KEY `last_online` (`last_online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_habbicons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_habbicons` (
  `user_id` int(11) NOT NULL,
  `habbicon_id` int(11) NOT NULL,
  `state` tinyint(4) NOT NULL DEFAULT 2,
  `unseen` tinyint(1) NOT NULL DEFAULT 0,
  `last_used` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`habbicon_id`),
  KEY `habbicon_id` (`habbicon_id`),
  KEY `recent` (`user_id`,`last_used`),
  CONSTRAINT `fk_users_habbicons_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_habbicons_ibfk_1` FOREIGN KEY (`habbicon_id`) REFERENCES `habbicons` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`state` in (1,2,3))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_reward_track_prizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_reward_track_prizes` (
  `user_id` int(11) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `prize_id` varchar(64) NOT NULL,
  `claimed_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`track_id`,`prize_id`),
  CONSTRAINT `fk_users_reward_track_prizes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_reward_track_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_reward_track_tasks` (
  `user_id` int(11) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `task_id` varchar(64) NOT NULL,
  `progress_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`track_id`,`task_id`),
  CONSTRAINT `fk_users_reward_track_tasks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_reward_tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_reward_tracks` (
  `user_id` int(11) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `premium` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`track_id`),
  KEY `fk_users_reward_tracks_track_id` (`track_id`),
  CONSTRAINT `fk_users_reward_tracks_track_id` FOREIGN KEY (`track_id`) REFERENCES `reward_tracks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_users_reward_tracks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_settings` (
  `user_id` int(11) NOT NULL,
  `home_room` int(10) NOT NULL DEFAULT 0,
  `block_newfriends` tinyint(1) NOT NULL DEFAULT 0,
  `volume` varchar(15) NOT NULL DEFAULT '100,100,100',
  `focus_preference` tinyint(1) NOT NULL DEFAULT 0,
  `chat_preference` tinyint(1) NOT NULL DEFAULT 0,
  `pets_muted` tinyint(1) NOT NULL DEFAULT 0,
  `bots_muted` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_invites` tinyint(1) NOT NULL DEFAULT 0,
  `allow_gifts` tinyint(1) NOT NULL DEFAULT 1,
  `friend_bar_state` tinyint(1) NOT NULL DEFAULT 1,
  `disable_forced_effects` tinyint(1) NOT NULL DEFAULT 0,
  `allow_mimic` tinyint(1) NOT NULL DEFAULT 1,
  `home_room_ref` int(10) GENERATED ALWAYS AS (nullif(`home_room`,0)) STORED,
  PRIMARY KEY (`user_id`),
  KEY `fk_users_settings_home_room_ref` (`home_room_ref`),
  CONSTRAINT `fk_users_settings_home_room_ref` FOREIGN KEY (`home_room_ref`) REFERENCES `rooms` (`id`),
  CONSTRAINT `fk_users_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wired_item_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_item_configurations` (
  `item_id` int(10) unsigned NOT NULL,
  `box_name` varchar(100) NOT NULL,
  `schema_version` int(11) NOT NULL,
  `configuration` longtext NOT NULL,
  PRIMARY KEY (`item_id`),
  CONSTRAINT `fk_wired_item_configurations_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wired_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_items` (
  `id` int(10) unsigned NOT NULL,
  `items` varchar(5000) NOT NULL,
  `delay` int(11) NOT NULL,
  `string` varchar(5000) NOT NULL,
  `bool` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_wired_items_id` FOREIGN KEY (`id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wired_reward_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_reward_state` (
  `item_id` int(10) unsigned NOT NULL,
  `claims` longtext NOT NULL,
  PRIMARY KEY (`item_id`),
  CONSTRAINT `fk_wired_reward_state_item_id` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wired_variable_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_variable_locks` (
  `definition_id` int(10) unsigned NOT NULL,
  `retired` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wired_variable_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_variable_values` (
  `definition_id` int(10) unsigned NOT NULL,
  `target_kind` tinyint(3) unsigned NOT NULL,
  `holder_id` bigint(20) NOT NULL,
  `value` int(11) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `updated_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`definition_id`,`target_kind`,`holder_id`),
  KEY `definition_holder` (`definition_id`,`holder_id`),
  KEY `definition_kind_value` (`definition_id`,`target_kind`,`value`),
  CONSTRAINT `fk_wired_variable_values_definition_id` FOREIGN KEY (`definition_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wordfilter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wordfilter` (
  `word` varchar(100) NOT NULL,
  `replacement` varchar(255) NOT NULL DEFAULT 'Habboon',
  `strict` tinyint(1) NOT NULL DEFAULT 1,
  `bannable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

