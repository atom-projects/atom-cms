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
) ENGINE=InnoDB AUTO_INCREMENT=544 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievements_talents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('citizenship','status') NOT NULL DEFAULT 'citizenship',
  `parent_category` int(11) NOT NULL DEFAULT -1,
  `level` int(11) NOT NULL,
  `order_num` int(11) NOT NULL,
  `achievement_group` varchar(255) NOT NULL DEFAULT 'ACH_',
  `achievement_level` int(11) NOT NULL DEFAULT 1,
  `prize` varchar(255) NOT NULL DEFAULT 'A1 KUMIANKKA',
  `prize_baseitem` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `acl_permissions` (
  `key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `category` varchar(64) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `is_orphan` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ambassador_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target` varchar(50) NOT NULL DEFAULT '',
  `sanctions_type` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `badge_definitions` (
  `code` varchar(35) NOT NULL,
  `required_right` varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY (`code`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `appeal_state` enum('0','1','2') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `value` (`value`) USING BTREE,
  KEY `bantype` (`bantype`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `ai_type` enum('generic','bartender','pet') NOT NULL DEFAULT 'generic',
  `name` varchar(100) NOT NULL,
  `motto` varchar(120) NOT NULL,
  `look` text NOT NULL,
  `x` int(11) NOT NULL DEFAULT 0,
  `y` int(11) NOT NULL DEFAULT 0,
  `z` int(11) NOT NULL DEFAULT 0,
  `rotation` int(11) NOT NULL DEFAULT 0,
  `walk_mode` enum('stand','freeroam','specified_range') NOT NULL DEFAULT 'freeroam',
  `min_x` int(11) NOT NULL DEFAULT 0,
  `min_y` int(11) NOT NULL DEFAULT 0,
  `max_x` int(11) NOT NULL DEFAULT 0,
  `max_y` int(11) NOT NULL DEFAULT 0,
  `effect` int(2) NOT NULL DEFAULT 0,
  `gender` varchar(5) NOT NULL DEFAULT 'M',
  `dance` int(11) NOT NULL DEFAULT 0,
  `automatic_chat` enum('false','true') NOT NULL DEFAULT 'false',
  `speaking_interval` int(8) NOT NULL DEFAULT 30,
  `mix_sentences` tinyint(1) NOT NULL DEFAULT 0,
  `chat_bubble` int(11) NOT NULL DEFAULT 2,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  KEY `ai_type` (`ai_type`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_pet_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `input_title` varchar(255) NOT NULL,
  `input` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_pet_responses` (
  `pet_id` varchar(255) NOT NULL,
  `responses` text NOT NULL,
  PRIMARY KEY (`pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bots_speech` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bot_id` int(10) unsigned NOT NULL,
  `text` varchar(200) NOT NULL,
  `shout` tinyint(1) NOT NULL DEFAULT 0,
  `type` enum('normal','rentable') DEFAULT 'normal',
  PRIMARY KEY (`id`),
  KEY `bot_id` (`bot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_accounts` (
  `user_id` int(11) NOT NULL,
  `last_publish_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_competition_entries` (
  `media_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`media_id`),
  KEY `owner_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_media` (
  `id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_publications` (
  `media_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`media_id`),
  KEY `owner_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `camera_purchases` (
  `item_id` int(10) unsigned NOT NULL,
  `media_id` char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime(6) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `media_id` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`user_id`,`quota_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3571 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_clothing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clothing_name` varchar(55) NOT NULL DEFAULT '',
  `clothing_parts` varchar(85) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `items` text NOT NULL,
  `name` varchar(35) NOT NULL,
  `room_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `item_id` varchar(120) NOT NULL,
  `catalog_name` varchar(100) NOT NULL,
  `cost_credits` int(11) NOT NULL DEFAULT 3,
  `cost_pixels` int(11) NOT NULL DEFAULT 0,
  `cost_diamonds` int(11) NOT NULL DEFAULT 0,
  `amount` int(11) NOT NULL DEFAULT 1,
  `limited_sells` int(11) NOT NULL DEFAULT 0,
  `limited_stack` int(11) NOT NULL DEFAULT 0,
  `offer_active` tinyint(1) NOT NULL DEFAULT 1,
  `extradata` varchar(1024) NOT NULL DEFAULT '',
  `badge` varchar(64) NOT NULL DEFAULT '',
  `offer_id` int(11) NOT NULL DEFAULT -1,
  `habbicon_id` int(11) NOT NULL DEFAULT 0,
  `club_level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `preview_image` varchar(255) NOT NULL DEFAULT '',
  `order_num` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `item_ids` (`item_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=88805521 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_marketplace_data` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `sprite` int(7) NOT NULL,
  `sold` int(7) NOT NULL DEFAULT 0,
  `avgprice` int(9) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_marketplace_offers` (
  `offer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
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
  PRIMARY KEY (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT -1,
  `caption` varchar(128) NOT NULL,
  `icon_image` int(11) NOT NULL DEFAULT 1,
  `required_permission` varchar(191) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `order_num` int(11) NOT NULL,
  `page_link` varchar(128) NOT NULL DEFAULT '',
  `page_layout` varchar(64) NOT NULL DEFAULT 'default_3x3',
  `page_strings_1` text NOT NULL,
  `page_strings_2` text NOT NULL,
  `visible` bit(1) NOT NULL DEFAULT b'1',
  `enabled` bit(1) NOT NULL DEFAULT b'1',
  `required_club_level` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `order_num` (`order_num`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=912364 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_pet_races` (
  `raceid` int(255) DEFAULT NULL,
  `color1` int(255) DEFAULT NULL,
  `color2` int(255) DEFAULT NULL,
  `has1color` tinyint(1) DEFAULT NULL,
  `has2color` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_promotions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(128) DEFAULT '',
  `image` varchar(255) DEFAULT '',
  `unknown` int(11) DEFAULT 0,
  `page_link` varchar(128) DEFAULT '',
  `parent_id` int(11) DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `item_type` tinyint(4) NOT NULL DEFAULT 0,
  `offer_id` int(11) NOT NULL DEFAULT -1,
  `product_code` varchar(128) NOT NULL DEFAULT '',
  `expires_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `room_id` (`room_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=501 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chatlogs_console_invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_external_badge_texts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `badge_code` varchar(35) NOT NULL DEFAULT '',
  `badge_title` varchar(75) NOT NULL DEFAULT '',
  `badge_desc` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=702 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_external_texts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(70) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_credit_spending` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `credits` int(11) NOT NULL,
  `spent_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`spent_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_gift_claims` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `gift_number` int(11) NOT NULL,
  `catalog_item_id` int(11) NOT NULL,
  `claimed_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`gift_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_gift_offers` (
  `catalog_item_id` int(11) NOT NULL,
  `days_required` int(11) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`catalog_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_membership_intervals` (
  `user_id` int(11) NOT NULL,
  `started_at` datetime(6) NOT NULL,
  `expires_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `club_paydays` (
  `user_id` int(11) NOT NULL,
  `payday` datetime(6) NOT NULL,
  `spent` int(11) NOT NULL,
  `streak_bonus` int(11) NOT NULL,
  `spending_bonus` int(11) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`,`payday`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `clothing_id` int(11) NOT NULL DEFAULT 0,
  `extra_rot` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `sprite_id` (`sprite_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1000000237 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `socket_policy_port` varchar(25) NOT NULL DEFAULT '',
  `game_enabled` tinyint(1) DEFAULT 1,
  `last_reset` double DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_memberships` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `rank` enum('0','1','2') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `groupid` (`group_id`),
  KEY `userid` (`user_id`),
  KEY `rank` (`rank`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_requests` (
  `group_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  KEY `groupid` (`group_id`),
  KEY `userid` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `badge` varchar(50) NOT NULL,
  `owner_id` int(11) unsigned NOT NULL,
  `created` datetime(6) DEFAULT NULL,
  `room_id` int(10) unsigned NOT NULL DEFAULT 0,
  `state` enum('0','1','2') NOT NULL DEFAULT '0',
  `colour1` int(11) NOT NULL DEFAULT 242424,
  `colour2` int(11) NOT NULL DEFAULT 242424,
  `admindeco` tinyint(1) NOT NULL DEFAULT 1,
  `forum_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `room_id` (`room_id`),
  KEY `owner` (`owner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups_items` (
  `type` enum('base','symbol','color','color2','color3') NOT NULL,
  `id` int(255) NOT NULL,
  `firstvalue` varchar(255) NOT NULL,
  `secondvalue` varchar(2000) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `housekeeping_online_peaks` (
  `day` date NOT NULL,
  `peak` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `room_id` int(10) unsigned NOT NULL DEFAULT 0,
  `base_item` int(10) unsigned NOT NULL,
  `extra_data` text NOT NULL,
  `x` int(11) NOT NULL DEFAULT 0,
  `y` int(11) NOT NULL DEFAULT 0,
  `z` double NOT NULL DEFAULT 0,
  `rot` int(11) NOT NULL DEFAULT 0,
  `wall_pos` varchar(100) DEFAULT '',
  `limited_number` int(11) DEFAULT 0,
  `limited_stack` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`) USING BTREE,
  KEY `userid` (`user_id`) USING BTREE,
  KEY `roomid` (`room_id`),
  KEY `base_item` (`base_item`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `items_youtube` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `youtube_id` varchar(35) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `description` varchar(150) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_client_namechange` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `new_name` varchar(50) NOT NULL DEFAULT '',
  `old_name` varchar(50) NOT NULL DEFAULT '',
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_client_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `data_string` text NOT NULL,
  `machine_id` varchar(75) NOT NULL DEFAULT '',
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_friendships` (
  `user_one_id` int(10) unsigned NOT NULL,
  `user_two_id` int(10) unsigned NOT NULL,
  `relationship` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_one_id`,`user_two_id`),
  KEY `user_one_id` (`user_one_id`),
  KEY `user_two_id` (`user_two_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_offline_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_id` int(11) unsigned NOT NULL DEFAULT 0,
  `from_id` int(11) unsigned NOT NULL DEFAULT 0,
  `message` varchar(255) NOT NULL,
  `timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messenger_requests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `from_id` int(10) unsigned NOT NULL,
  `to_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `to_id` (`to_id`),
  KEY `from_id` (`from_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_preset_action_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_presets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('user','room') NOT NULL DEFAULT 'user',
  `message` text NOT NULL,
  `enabled` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `score` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `status` enum('open','picked','resolved','abusive','invalid','deleted') NOT NULL DEFAULT 'open',
  `sender_id` int(10) unsigned NOT NULL,
  `reported_id` int(10) unsigned NOT NULL,
  `moderator_id` int(10) unsigned NOT NULL,
  `message` text NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `timestamp` double NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `status` (`status`),
  KEY `reported_id` (`reported_id`),
  KEY `moderator_id` (`moderator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_topic_actions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `caption` varchar(225) NOT NULL DEFAULT '',
  `message_text` varchar(255) NOT NULL,
  `default_sanction` varchar(255) NOT NULL,
  `mute_time` int(11) NOT NULL,
  `ban_time` int(11) NOT NULL,
  `ip_time` int(11) NOT NULL,
  `trade_lock_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `moderation_topics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `caption` varchar(225) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `ordernum` (`order_num`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=182 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`track_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_track_tasks` (
  `track_id` varchar(64) NOT NULL,
  `id` varchar(64) NOT NULL,
  `action_type` varchar(64) NOT NULL,
  `parameter` varchar(255) NOT NULL DEFAULT '',
  `premium` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`track_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_limits` (
  `role_id` int(11) NOT NULL,
  `limit_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`limit_key`),
  CONSTRAINT `role_limits_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_key` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`role_id`,`permission_key`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_bans` (
  `user_id` int(11) unsigned NOT NULL DEFAULT 0,
  `room_id` int(11) unsigned NOT NULL DEFAULT 0,
  `expire` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`room_id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_chat_styles` (
  `id` int(11) NOT NULL,
  `name` varchar(25) DEFAULT '',
  `required_permission` varchar(191) DEFAULT '',
  `requires_hc` tinyint(1) NOT NULL DEFAULT 0,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_filter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(15) NOT NULL DEFAULT '',
  `room_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_items_tele_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tele_one_id` int(10) unsigned NOT NULL,
  `tele_two_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tele_one_id` (`tele_one_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_items_toner` (
  `id` int(11) unsigned NOT NULL,
  `enabled` tinyint(1) DEFAULT 0,
  `data1` int(11) NOT NULL,
  `data2` int(11) NOT NULL,
  `data3` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `poolmap` varchar(100) NOT NULL DEFAULT '',
  `custom` tinyint(1) NOT NULL DEFAULT 0,
  `wall_height` int(11) NOT NULL DEFAULT -1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_promotions` (
  `room_id` int(11) NOT NULL,
  `title` varchar(35) NOT NULL DEFAULT '',
  `description` varchar(220) NOT NULL DEFAULT '',
  `timestamp_start` datetime(6) DEFAULT NULL,
  `timestamp_expire` datetime(6) DEFAULT NULL,
  `category_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `room_wired_settings` (
  `room_id` int(10) unsigned NOT NULL,
  `inspect_mask` int(11) NOT NULL DEFAULT 2,
  `modify_mask` int(11) NOT NULL DEFAULT 2,
  `timezone` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rooms` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `roomtype` enum('public','private') NOT NULL DEFAULT 'private',
  `caption` varchar(100) NOT NULL DEFAULT 'Room',
  `owner` varchar(75) NOT NULL DEFAULT '',
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `owner` (`owner`),
  KEY `users_now` (`users_now`),
  KEY `roomtype` (`roomtype`),
  KEY `caption` (`caption`),
  KEY `score` (`score`),
  KEY `category` (`category`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_locale` (
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_reward_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_settings` (
  `key` varchar(255) NOT NULL DEFAULT 'server.variable',
  `value` text NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `server_status` (
  `users_online` int(11) NOT NULL DEFAULT 0,
  `loaded_rooms` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `users_online` (`users_online`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('citizenship','helper') NOT NULL,
  `level` int(11) DEFAULT 0,
  `data_actions` text NOT NULL,
  `data_gifts` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `talents_sub_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `talent_level` int(11) NOT NULL DEFAULT 0,
  `sub_level` int(11) NOT NULL DEFAULT 0,
  `badge_code` varchar(45) NOT NULL DEFAULT '',
  `required_progress` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=185 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_achievements` (
  `userid` int(11) unsigned NOT NULL,
  `group` varchar(255) NOT NULL,
  `level` int(11) NOT NULL,
  `progress` int(11) NOT NULL,
  PRIMARY KEY (`userid`,`group`),
  KEY `id` (`userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `badge_id` varchar(100) NOT NULL,
  `badge_slot` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id, badge_id` (`user_id`,`badge_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_clothing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `part_id` varchar(25) NOT NULL,
  `part` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_effects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `effect_id` int(11) DEFAULT 1,
  `total_duration` int(11) DEFAULT 3600,
  `is_activated` tinyint(1) DEFAULT 0,
  `quantity` int(11) DEFAULT 0,
  `activated_stamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_ignores` (
  `user_id` int(10) unsigned NOT NULL,
  `ignore_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`ignore_id`),
  KEY `user_id` (`user_id`),
  KEY `ignore_id` (`ignore_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_info` (
  `user_id` int(11) NOT NULL,
  `bans` int(11) NOT NULL DEFAULT 0,
  `cautions` int(11) NOT NULL DEFAULT 0,
  `reg_timestamp` double NOT NULL DEFAULT 0,
  `login_timestamp` double NOT NULL DEFAULT 0,
  `cfhs` int(11) NOT NULL DEFAULT 0,
  `cfhs_abusive` int(11) NOT NULL DEFAULT 0,
  `trading_locks_count` int(11) NOT NULL DEFAULT 0,
  `trading_locked` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_presents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `base_id` int(10) unsigned NOT NULL,
  `extra_data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_quests` (
  `user_id` int(10) unsigned NOT NULL,
  `quest_id` int(10) unsigned NOT NULL,
  `progress` int(10) DEFAULT 0,
  PRIMARY KEY (`user_id`,`quest_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_roomvisits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `entry_timestamp` datetime(6) DEFAULT NULL,
  `exit_timestamp` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `entry_timestamp` (`entry_timestamp`),
  KEY `exit_timestamp` (`exit_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=349 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_saved_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `filter` varchar(65) NOT NULL DEFAULT '',
  `search_code` varchar(65) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `value` (`search_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` char(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `revoked_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_statistics` (
  `id` int(7) NOT NULL,
  `RoomVisits` int(7) NOT NULL DEFAULT 0,
  `OnlineTime` int(7) NOT NULL DEFAULT 0,
  `Respect` int(6) NOT NULL DEFAULT 0,
  `RespectGiven` int(6) NOT NULL DEFAULT 0,
  `GiftsGiven` int(6) NOT NULL DEFAULT 0,
  `GiftsReceived` int(6) NOT NULL DEFAULT 0,
  `DailyRespectPoints` int(1) NOT NULL DEFAULT 3,
  `DailyPetRespectPoints` int(1) NOT NULL DEFAULT 3,
  `AchievementScore` int(7) NOT NULL DEFAULT 0,
  `quest_id` int(10) unsigned NOT NULL DEFAULT 0,
  `quest_progress` int(10) NOT NULL DEFAULT 0,
  `lev_builder` int(10) NOT NULL DEFAULT 0,
  `lev_social` int(10) NOT NULL DEFAULT 0,
  `lev_identity` int(10) NOT NULL DEFAULT 0,
  `lev_explore` int(10) NOT NULL DEFAULT 0,
  `groupid` int(11) NOT NULL DEFAULT 0,
  `tickets_answered` int(11) NOT NULL DEFAULT 0,
  `respectsTimestamp` varchar(6) DEFAULT '10/19',
  `forum_posts` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `OnlineTime` (`OnlineTime`),
  KEY `Respect` (`Respect`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `voucher` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id, voucher` (`user_id`,`voucher`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_wardrobe` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `slot_id` int(10) unsigned NOT NULL,
  `look` varchar(120) NOT NULL,
  `gender` enum('F','M') NOT NULL DEFAULT 'M',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `slot_id` (`slot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  `vip_points` int(11) DEFAULT 0,
  `activity_points` int(11) DEFAULT 5000,
  `look` char(255) DEFAULT NULL,
  `gender` enum('M','F') DEFAULT 'M',
  `motto` char(50) DEFAULT NULL,
  `online` tinyint(1) DEFAULT 0,
  `ip_last` varchar(45) DEFAULT '',
  `ip_reg` varchar(45) DEFAULT NULL,
  `vip` tinyint(1) DEFAULT 1,
  `machine_id` varchar(125) DEFAULT '',
  `gotw_points` int(11) DEFAULT 0,
  `time_muted` double DEFAULT 0,
  `trading_locked` double DEFAULT 0,
  `bubble_id` tinyint(4) NOT NULL DEFAULT 0,
  `credential_generation` int(11) unsigned NOT NULL DEFAULT 0,
  `auth_ticket_session` char(32) DEFAULT NULL,
  `account_created` datetime DEFAULT NULL,
  `last_online` datetime DEFAULT NULL,
  `last_change` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  KEY `rank` (`rank`),
  KEY `ip_last` (`ip_last`),
  KEY `ip_reg` (`ip_reg`),
  KEY `credits` (`credits`),
  KEY `activity_points` (`activity_points`),
  KEY `online` (`online`),
  KEY `mail` (`mail`),
  KEY `machine_id` (`machine_id`),
  KEY `auth_ticket` (`auth_ticket`),
  KEY `last_online` (`last_online`),
  KEY `messenger` (`id`,`username`,`look`,`motto`,`last_online`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
  CONSTRAINT `users_habbicons_ibfk_1` FOREIGN KEY (`habbicon_id`) REFERENCES `habbicons` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`state` in (1,2,3))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_reward_track_prizes` (
  `user_id` int(11) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `prize_id` varchar(64) NOT NULL,
  `claimed_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`user_id`,`track_id`,`prize_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_reward_track_tasks` (
  `user_id` int(11) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `task_id` varchar(64) NOT NULL,
  `progress_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`track_id`,`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_reward_tracks` (
  `user_id` int(11) NOT NULL,
  `track_id` varchar(64) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `premium` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_settings` (
  `user_id` int(11) NOT NULL,
  `home_room` int(10) unsigned NOT NULL DEFAULT 0,
  `is_muted` tinyint(1) NOT NULL DEFAULT 0,
  `block_newfriends` tinyint(1) NOT NULL DEFAULT 0,
  `hide_online` tinyint(1) NOT NULL DEFAULT 0,
  `hide_inroom` tinyint(1) NOT NULL DEFAULT 0,
  `volume` varchar(15) NOT NULL DEFAULT '100,100,100',
  `focus_preference` tinyint(1) NOT NULL DEFAULT 0,
  `chat_preference` tinyint(1) NOT NULL DEFAULT 0,
  `pets_muted` tinyint(1) NOT NULL DEFAULT 0,
  `bots_muted` tinyint(1) NOT NULL DEFAULT 0,
  `advertising_report_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `ignore_invites` tinyint(1) NOT NULL DEFAULT 0,
  `allow_gifts` tinyint(1) NOT NULL DEFAULT 1,
  `friend_bar_state` tinyint(1) NOT NULL DEFAULT 1,
  `disable_forced_effects` tinyint(1) NOT NULL DEFAULT 0,
  `allow_mimic` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_users_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_item_configurations` (
  `item_id` int(10) unsigned NOT NULL,
  `box_name` varchar(100) NOT NULL,
  `schema_version` int(11) NOT NULL,
  `configuration` longtext NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_items` (
  `id` int(11) NOT NULL,
  `items` varchar(5000) NOT NULL,
  `delay` int(11) NOT NULL,
  `string` varchar(5000) NOT NULL,
  `bool` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_reward_state` (
  `item_id` int(10) unsigned NOT NULL,
  `claims` longtext NOT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_variable_locks` (
  `definition_id` int(10) unsigned NOT NULL,
  `retired` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wired_variable_values` (
  `definition_id` int(10) unsigned NOT NULL,
  `target_kind` tinyint(3) unsigned NOT NULL,
  `holder_id` bigint(20) NOT NULL,
  `value` int(11) NOT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `updated_at` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`definition_id`,`target_kind`,`holder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `wordfilter` (
  `word` varchar(100) NOT NULL,
  `replacement` varchar(255) NOT NULL DEFAULT 'Habboon',
  `strict` tinyint(1) NOT NULL DEFAULT 1,
  `addedby` varchar(100) NOT NULL DEFAULT '',
  `bannable` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`word`),
  UNIQUE KEY `word` (`word`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

