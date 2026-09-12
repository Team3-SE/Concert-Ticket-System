-- Concert Ticketing System schema
-- Target database: MySQL 8.0+
-- Generated from Laravel migrations in this project.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `concert_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `concert_db`;

DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `migrations`;

DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `anomaly_attachment`;
DROP TABLE IF EXISTS `system_anomaly`;
DROP TABLE IF EXISTS `seat_lock_log`;
DROP TABLE IF EXISTS `ticket_validation_log`;
DROP TABLE IF EXISTS `staff_concert`;
DROP TABLE IF EXISTS `ticket`;
DROP TABLE IF EXISTS `customer_order`;
DROP TABLE IF EXISTS `chargeback`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `booking_standing_item`;
DROP TABLE IF EXISTS `booking_seat`;
DROP TABLE IF EXISTS `booking`;
DROP TABLE IF EXISTS `seat`;
DROP TABLE IF EXISTS `zone_seat_pattern`;
DROP TABLE IF EXISTS `zone`;
DROP TABLE IF EXISTS `concert_contact`;
DROP TABLE IF EXISTS `concert`;
DROP TABLE IF EXISTS `admin`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `event_organizer`;
DROP TABLE IF EXISTS `customer`;
DROP TABLE IF EXISTS `app_user`;
DROP TABLE IF EXISTS `company`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` char(26) NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext NULL,
  `cancelled_at` int NULL,
  `created_at` int NOT NULL,
  `finished_at` int NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `company` (
  `company_id` char(26) NOT NULL,
  `name` varchar(255) NOT NULL,
  `initials` varchar(255) NOT NULL,
  `description` text NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_user` (
  `user_id` char(26) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `account_status` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customer` (
  `customer_id` char(26) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `customer_email_unique` (`email`),
  UNIQUE KEY `customer_phone_unique` (`phone`),
  CONSTRAINT `customer_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_organizer` (
  `organizer_id` char(26) NOT NULL,
  `company_id` char(26) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`organizer_id`),
  UNIQUE KEY `event_organizer_email_unique` (`email`),
  UNIQUE KEY `event_organizer_username_unique` (`username`),
  KEY `event_organizer_company_id_foreign` (`company_id`),
  CONSTRAINT `event_organizer_organizer_id_foreign`
    FOREIGN KEY (`organizer_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `event_organizer_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `staff` (
  `staff_id` char(26) NOT NULL,
  `company_id` char(26) NOT NULL,
  `created_by_organizer_id` char(26) NULL,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `staff_username_unique` (`username`),
  KEY `staff_company_id_foreign` (`company_id`),
  KEY `staff_created_by_organizer_id_foreign` (`created_by_organizer_id`),
  CONSTRAINT `staff_staff_id_foreign`
    FOREIGN KEY (`staff_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `staff_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE CASCADE,
  CONSTRAINT `staff_created_by_organizer_id_foreign`
    FOREIGN KEY (`created_by_organizer_id`) REFERENCES `event_organizer` (`organizer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admin` (
  `admin_id` char(26) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `admin_email_unique` (`email`),
  UNIQUE KEY `admin_username_unique` (`username`),
  CONSTRAINT `admin_admin_id_foreign`
    FOREIGN KEY (`admin_id`) REFERENCES `app_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `concert` (
  `concert_id` char(26) NOT NULL,
  `company_id` char(26) NOT NULL,
  `organizer_id` char(26) NOT NULL,
  `name` varchar(255) NOT NULL,
  `artist` varchar(255) NOT NULL,
  `venue` varchar(255) NOT NULL,
  `event_datetime` datetime NOT NULL,
  `description` text NULL,
  `poster_path` varchar(255) NULL,
  `seat_map_image_path` varchar(255) NULL,
  `sale_start_datetime` datetime NOT NULL,
  `sale_end_datetime` datetime NOT NULL,
  `max_ticket_per_order` int NOT NULL,
  `status` varchar(255) NOT NULL,
  `concert_code` varchar(255) NOT NULL,
  PRIMARY KEY (`concert_id`),
  UNIQUE KEY `concert_concert_code_unique` (`concert_code`),
  KEY `concert_company_id_foreign` (`company_id`),
  KEY `concert_organizer_id_foreign` (`organizer_id`),
  CONSTRAINT `concert_company_id_foreign`
    FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE RESTRICT,
  CONSTRAINT `concert_organizer_id_foreign`
    FOREIGN KEY (`organizer_id`) REFERENCES `event_organizer` (`organizer_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `concert_contact` (
  `contact_id` char(26) NOT NULL,
  `concert_id` char(26) NOT NULL,
  `contact_type` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`contact_id`),
  UNIQUE KEY `concert_contact_concert_id_contact_type_unique` (`concert_id`, `contact_type`),
  CONSTRAINT `concert_contact_concert_id_foreign`
    FOREIGN KEY (`concert_id`) REFERENCES `concert` (`concert_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `zone` (
  `zone_id` char(26) NOT NULL,
  `concert_id` char(26) NOT NULL,
  `name` varchar(255) NOT NULL,
  `zone_type` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `color` varchar(255) NOT NULL,
  PRIMARY KEY (`zone_id`),
  KEY `zone_concert_id_foreign` (`concert_id`),
  CONSTRAINT `zone_concert_id_foreign`
    FOREIGN KEY (`concert_id`) REFERENCES `concert` (`concert_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `zone_seat_pattern` (
  `pattern_id` char(26) NOT NULL,
  `zone_id` char(26) NOT NULL,
  `pattern_type` varchar(255) NOT NULL,
  `start_row` varchar(255) NOT NULL,
  `start_seat_number` int NOT NULL,
  `row_count` int NOT NULL,
  `seats_per_row` int NOT NULL,
  PRIMARY KEY (`pattern_id`),
  UNIQUE KEY `zone_seat_pattern_zone_id_unique` (`zone_id`),
  CONSTRAINT `zone_seat_pattern_zone_id_foreign`
    FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seat` (
  `seat_id` char(26) NOT NULL,
  `zone_id` char(26) NOT NULL,
  `row` varchar(255) NOT NULL,
  `number` int NOT NULL,
  `status` varchar(255) NOT NULL,
  PRIMARY KEY (`seat_id`),
  UNIQUE KEY `seat_zone_id_row_number_unique` (`zone_id`, `row`, `number`),
  CONSTRAINT `seat_zone_id_foreign`
    FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking` (
  `booking_id` char(26) NOT NULL,
  `booking_code` varchar(255) NOT NULL,
  `customer_id` char(26) NOT NULL,
  `concert_id` char(26) NOT NULL,
  `booking_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL,
  `expiry_time` datetime NOT NULL,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `booking_booking_code_unique` (`booking_code`),
  KEY `booking_customer_id_foreign` (`customer_id`),
  KEY `booking_concert_id_foreign` (`concert_id`),
  CONSTRAINT `booking_customer_id_foreign`
    FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE RESTRICT,
  CONSTRAINT `booking_concert_id_foreign`
    FOREIGN KEY (`concert_id`) REFERENCES `concert` (`concert_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking_seat` (
  `booking_id` char(26) NOT NULL,
  `seat_id` char(26) NOT NULL,
  `zone_name_snapshot` varchar(255) NOT NULL,
  `seat_label_snapshot` varchar(255) NOT NULL,
  `unit_price_snapshot` decimal(10,2) NOT NULL,
  PRIMARY KEY (`booking_id`, `seat_id`),
  KEY `booking_seat_seat_id_foreign` (`seat_id`),
  CONSTRAINT `booking_seat_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `booking_seat_seat_id_foreign`
    FOREIGN KEY (`seat_id`) REFERENCES `seat` (`seat_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking_standing_item` (
  `booking_id` char(26) NOT NULL,
  `zone_id` char(26) NOT NULL,
  `quantity` int NOT NULL,
  `zone_name_snapshot` varchar(255) NOT NULL,
  `unit_price_snapshot` decimal(10,2) NOT NULL,
  PRIMARY KEY (`booking_id`, `zone_id`),
  KEY `booking_standing_item_zone_id_foreign` (`zone_id`),
  CONSTRAINT `booking_standing_item_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE CASCADE,
  CONSTRAINT `booking_standing_item_zone_id_foreign`
    FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment` (
  `payment_id` char(26) NOT NULL,
  `booking_id` char(26) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `payment_date` datetime NULL,
  `provider_transaction_id` varchar(255) NULL,
  `decline_code` varchar(255) NULL,
  `risk_level` varchar(255) NULL,
  `card_fingerprint` varchar(255) NULL,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `payment_booking_id_unique` (`booking_id`),
  UNIQUE KEY `payment_provider_transaction_id_unique` (`provider_transaction_id`),
  CONSTRAINT `payment_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chargeback` (
  `chargeback_id` char(26) NOT NULL,
  `payment_id` char(26) NOT NULL,
  `provider_chargeback_id` varchar(255) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL,
  `resolved_at` datetime NULL,
  PRIMARY KEY (`chargeback_id`),
  UNIQUE KEY `chargeback_provider_chargeback_id_unique` (`provider_chargeback_id`),
  KEY `chargeback_payment_id_foreign` (`payment_id`),
  CONSTRAINT `chargeback_payment_id_foreign`
    FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customer_order` (
  `order_id` char(26) NOT NULL,
  `order_code` varchar(255) NOT NULL,
  `booking_id` char(26) NOT NULL,
  `payment_id` char(26) NOT NULL,
  `order_date` datetime NOT NULL,
  `concert_name_snapshot` varchar(255) NOT NULL,
  `artist_snapshot` varchar(255) NOT NULL,
  `venue_snapshot` varchar(255) NOT NULL,
  `event_datetime_snapshot` datetime NOT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `customer_order_order_code_unique` (`order_code`),
  UNIQUE KEY `customer_order_booking_id_unique` (`booking_id`),
  UNIQUE KEY `customer_order_payment_id_unique` (`payment_id`),
  CONSTRAINT `customer_order_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE RESTRICT,
  CONSTRAINT `customer_order_payment_id_foreign`
    FOREIGN KEY (`payment_id`) REFERENCES `payment` (`payment_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ticket` (
  `ticket_id` char(26) NOT NULL,
  `ticket_code` varchar(255) NOT NULL,
  `order_id` char(26) NOT NULL,
  `concert_id` char(26) NOT NULL,
  `zone_id` char(26) NOT NULL,
  `seat_id` char(26) NULL,
  `qr_code` varchar(255) NOT NULL,
  `is_used` tinyint(1) NOT NULL,
  `zone_name_snapshot` varchar(255) NOT NULL,
  `seat_label_snapshot` varchar(255) NULL,
  `unit_price_snapshot` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `ticket_ticket_code_unique` (`ticket_code`),
  UNIQUE KEY `ticket_seat_id_unique` (`seat_id`),
  UNIQUE KEY `ticket_qr_code_unique` (`qr_code`),
  KEY `ticket_order_id_foreign` (`order_id`),
  KEY `ticket_concert_id_foreign` (`concert_id`),
  KEY `ticket_zone_id_foreign` (`zone_id`),
  CONSTRAINT `ticket_order_id_foreign`
    FOREIGN KEY (`order_id`) REFERENCES `customer_order` (`order_id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_concert_id_foreign`
    FOREIGN KEY (`concert_id`) REFERENCES `concert` (`concert_id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_zone_id_foreign`
    FOREIGN KEY (`zone_id`) REFERENCES `zone` (`zone_id`) ON DELETE RESTRICT,
  CONSTRAINT `ticket_seat_id_foreign`
    FOREIGN KEY (`seat_id`) REFERENCES `seat` (`seat_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `staff_concert` (
  `staff_id` char(26) NOT NULL,
  `concert_id` char(26) NOT NULL,
  PRIMARY KEY (`staff_id`, `concert_id`),
  KEY `staff_concert_concert_id_foreign` (`concert_id`),
  CONSTRAINT `staff_concert_staff_id_foreign`
    FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE,
  CONSTRAINT `staff_concert_concert_id_foreign`
    FOREIGN KEY (`concert_id`) REFERENCES `concert` (`concert_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ticket_validation_log` (
  `validation_id` char(26) NOT NULL,
  `ticket_id` char(26) NOT NULL,
  `staff_id` char(26) NOT NULL,
  `validation_time` datetime NOT NULL,
  `result` tinyint(1) NOT NULL,
  PRIMARY KEY (`validation_id`),
  KEY `ticket_validation_log_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_validation_log_staff_id_foreign` (`staff_id`),
  CONSTRAINT `ticket_validation_log_ticket_id_foreign`
    FOREIGN KEY (`ticket_id`) REFERENCES `ticket` (`ticket_id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_validation_log_staff_id_foreign`
    FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `seat_lock_log` (
  `lock_id` char(26) NOT NULL,
  `booking_id` char(26) NULL,
  `seat_id` char(26) NOT NULL,
  `locked_at` datetime NOT NULL,
  `expired_at` datetime NOT NULL,
  `released_at` datetime NULL,
  `status` varchar(255) NOT NULL,
  PRIMARY KEY (`lock_id`),
  KEY `seat_lock_log_booking_id_foreign` (`booking_id`),
  KEY `seat_lock_log_seat_id_foreign` (`seat_id`),
  CONSTRAINT `seat_lock_log_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `seat_lock_log_seat_id_foreign`
    FOREIGN KEY (`seat_id`) REFERENCES `seat` (`seat_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_anomaly` (
  `anomaly_id` char(26) NOT NULL,
  `anomaly_code` varchar(255) NOT NULL,
  `booking_id` char(26) NULL,
  `reviewed_by_admin_id` char(26) NULL,
  `type` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `status` varchar(255) NOT NULL,
  `resolve_reason` text NULL,
  PRIMARY KEY (`anomaly_id`),
  UNIQUE KEY `system_anomaly_anomaly_code_unique` (`anomaly_code`),
  KEY `system_anomaly_booking_id_foreign` (`booking_id`),
  KEY `system_anomaly_reviewed_by_admin_id_foreign` (`reviewed_by_admin_id`),
  CONSTRAINT `system_anomaly_booking_id_foreign`
    FOREIGN KEY (`booking_id`) REFERENCES `booking` (`booking_id`) ON DELETE SET NULL,
  CONSTRAINT `system_anomaly_reviewed_by_admin_id_foreign`
    FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `anomaly_attachment` (
  `attachment_id` char(26) NOT NULL,
  `anomaly_id` char(26) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  PRIMARY KEY (`attachment_id`),
  KEY `anomaly_attachment_anomaly_id_foreign` (`anomaly_id`),
  CONSTRAINT `anomaly_attachment_anomaly_id_foreign`
    FOREIGN KEY (`anomaly_id`) REFERENCES `system_anomaly` (`anomaly_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_log` (
  `log_id` char(26) NOT NULL,
  `user_id` char(26) NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL,
  `ip_address` varchar(255) NULL,
  PRIMARY KEY (`log_id`),
  KEY `audit_log_user_id_foreign` (`user_id`),
  CONSTRAINT `audit_log_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `app_user` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_sessions_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table', 1),
  ('2026_08_28_000001_create_concert_ticketing_schema', 1);
