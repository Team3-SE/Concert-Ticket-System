-- -----------------------------------------------------
-- concert_ticketing_schema_v3.4.2.sql
-- โครงสร้างฐานข้อมูลเวอร์ชัน 3.4.2 แปลง (Forward Engineer) จากไฟล์โมเดล 3.4.2.mwb
-- แก้จากโมเดลต้นฉบับ 3 จุด (generated column อ้างชื่อคอลัมน์ที่ไม่มีอยู่จริง ทำให้ MySQL สร้างตารางไม่ได้)
--   seat_lock_log.active_seat_id : sll_released_at, sll_sold_at -> released_at, sold_at
--   ticket.active_seat_id        : t_status -> status
--   ticket.active_slot           : t_status, t_slot_no -> status, slot_no
-- -----------------------------------------------------

-- MySQL Script generated from MySQL Workbench model 3.4.2.mwb
-- Tue Nov 10 16:23:50 2026
-- Model: concert_ticketing    Version: 3.4.2
-- Forward Engineering (same layout as MySQL Workbench)

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema concert_ticketing
-- -----------------------------------------------------

CREATE SCHEMA IF NOT EXISTS `concert_ticketing` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `concert_ticketing` ;

-- -----------------------------------------------------
-- Table `concert_ticketing`.`organization`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`organization` (
  `org_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`org_id`),
  UNIQUE INDEX `org_name_UNIQUE` (`name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`app_user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`app_user` (
  `u_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NULL,
  `password_hash` VARCHAR(255) NULL,
  `role` VARCHAR(50) NOT NULL,
  `account_status` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `first_name` VARCHAR(100) NULL DEFAULT NULL,
  `last_name` VARCHAR(100) NULL DEFAULT NULL,
  `phone` VARCHAR(50) NULL DEFAULT NULL,
  `username` VARCHAR(45) NULL DEFAULT NULL,
  `u_created_by_app_user_id` INT NULL DEFAULT NULL,
  `u_organization_id` INT NULL DEFAULT NULL,
  PRIMARY KEY (`u_id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) VISIBLE,
  UNIQUE INDEX `u_phone_UNIQUE` (`phone` ASC) VISIBLE,
  UNIQUE INDEX `username_UNIQUE` (`username` ASC) VISIBLE,
  INDEX `fk_app_user_app_user1_idx` (`u_created_by_app_user_id` ASC) VISIBLE,
  INDEX `fk_app_user_organization1_idx` (`u_organization_id` ASC) VISIBLE,
  CONSTRAINT `fk_app_user_app_user1`
    FOREIGN KEY (`u_created_by_app_user_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`),
  CONSTRAINT `fk_app_user_organization1`
    FOREIGN KEY (`u_organization_id`)
    REFERENCES `concert_ticketing`.`organization` (`org_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`concert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`concert` (
  `c_id` INT NOT NULL AUTO_INCREMENT,
  `c_organization_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `artist` VARCHAR(255) NOT NULL,
  `venue` VARCHAR(500) NOT NULL,
  `event_datetime` DATETIME NULL,
  `description` TEXT NULL DEFAULT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(255) NULL,
  `facebook` VARCHAR(255) NULL,
  `line` VARCHAR(255) NULL,
  `poster_path` VARCHAR(500) NULL DEFAULT NULL,
  `seat_map_image_path` VARCHAR(500) NULL DEFAULT NULL,
  `sale_start_datetime` DATETIME NULL DEFAULT NULL,
  `sale_end_datetime` DATETIME NULL DEFAULT NULL,
  `max_ticket_per_order` INT NULL DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL,
  `concert_code` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`c_id`),
  UNIQUE INDEX `concert_code` (`concert_code` ASC) VISIBLE,
  INDEX `fk_concert_organization` (`c_organization_id` ASC) VISIBLE,
  CONSTRAINT `fk_concert_organization`
    FOREIGN KEY (`c_organization_id`)
    REFERENCES `concert_ticketing`.`organization` (`org_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`zone`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`zone` (
  `z_id` INT NOT NULL AUTO_INCREMENT,
  `z_concert_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `zone_type` VARCHAR(50) NOT NULL,
  `pattern_type` VARCHAR(50) NULL,
  `prefix` VARCHAR(50) NULL COMMENT 'used by prefix_auto_alpha only',
  `price` DECIMAL(12,2) NOT NULL,
  `capacity` INT NOT NULL,
  `total_rows` INT NULL,
  `seats_per_row` INT NULL,
  `seat_number_start` INT NULL DEFAULT 1,
  `row_alphabet_start` VARCHAR(50) NULL DEFAULT 'A',
  `color` VARCHAR(50) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `custom_items` JSON NULL,
  PRIMARY KEY (`z_id`),
  INDEX `fk_zone_concert` (`z_concert_id` ASC) VISIBLE,
  CONSTRAINT `fk_zone_concert`
    FOREIGN KEY (`z_concert_id`)
    REFERENCES `concert_ticketing`.`concert` (`c_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`booking`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`booking` (
  `b_id` INT NOT NULL AUTO_INCREMENT,
  `b_user_id` INT NOT NULL,
  `b_zone_id` INT NOT NULL,
  `booking_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_amount` DECIMAL(12,2) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `expiry_time` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `booking_code` VARCHAR(100) NOT NULL,
  `zone_price_snapshot` DECIMAL(12,2) NOT NULL,
  `zone_name_snapshot` VARCHAR(255) NOT NULL,
  `standing_quantity` INT NULL,
  PRIMARY KEY (`b_id`),
  INDEX `idx_booking_user_date` (`b_user_id` ASC, `booking_date` DESC) VISIBLE,
  UNIQUE INDEX `b_booking_code_UNIQUE` (`booking_code` ASC) VISIBLE,
  INDEX `idx_booking_expiry` (`status` ASC, `expiry_time` ASC) VISIBLE,
  INDEX `fk_booking_zone_idx` (`b_zone_id` ASC) VISIBLE,
  CONSTRAINT `fk_booking_user`
    FOREIGN KEY (`b_user_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`),
  CONSTRAINT `fk_booking_zone`
    FOREIGN KEY (`b_zone_id`)
    REFERENCES `concert_ticketing`.`zone` (`z_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`system_anomaly`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`system_anomaly` (
  `sa_id` INT NOT NULL AUTO_INCREMENT,
  `sa_booking_id` INT NOT NULL,
  `sa_managed_by_admin_id` INT NULL DEFAULT NULL,
  `type` VARCHAR(100) NOT NULL,
  `message` TEXT NULL DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL,
  `resolved_at` DATETIME NULL DEFAULT NULL,
  `acked_at` DATETIME NULL DEFAULT NULL,
  `resolve_reason` TEXT NULL DEFAULT NULL,
  `anomaly_code` VARCHAR(100) NOT NULL,
  `detected_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sa_caused_by_user_id` INT NULL,
  PRIMARY KEY (`sa_id`),
  UNIQUE INDEX `anomaly_code` (`anomaly_code` ASC) VISIBLE,
  INDEX `fk_anomaly_booking` (`sa_booking_id` ASC) VISIBLE,
  INDEX `fk_anomaly_admin` (`sa_managed_by_admin_id` ASC) VISIBLE,
  INDEX `fk_system_anomaly_app_user1_idx` (`sa_caused_by_user_id` ASC) VISIBLE,
  CONSTRAINT `fk_anomaly_admin`
    FOREIGN KEY (`sa_managed_by_admin_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`),
  CONSTRAINT `fk_anomaly_booking`
    FOREIGN KEY (`sa_booking_id`)
    REFERENCES `concert_ticketing`.`booking` (`b_id`),
  CONSTRAINT `fk_system_anomaly_app_user1`
    FOREIGN KEY (`sa_caused_by_user_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`anomaly_attachment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`anomaly_attachment` (
  `aa_id` INT NOT NULL AUTO_INCREMENT,
  `aa_anomaly_id` INT NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`aa_id`),
  INDEX `fk_attachment_anomaly` (`aa_anomaly_id` ASC) VISIBLE,
  CONSTRAINT `fk_attachment_anomaly`
    FOREIGN KEY (`aa_anomaly_id`)
    REFERENCES `concert_ticketing`.`system_anomaly` (`sa_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`audit_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`audit_log` (
  `al_id` INT NOT NULL AUTO_INCREMENT,
  `al_user_id` INT NULL,
  `type` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(100) NULL DEFAULT NULL,
  `metadata_details` JSON NULL DEFAULT NULL,
  PRIMARY KEY (`al_id`),
  INDEX `idx_audit_user_created` (`al_user_id` ASC, `created_at` ASC) VISIBLE,
  INDEX `idx_audit_type_created` (`type` ASC, `created_at` ASC) VISIBLE,
  INDEX `idx_audit_created` (`created_at` ASC) VISIBLE,
  CONSTRAINT `fk_audit_user`
    FOREIGN KEY (`al_user_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`seat`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`seat` (
  `s_id` INT NOT NULL AUTO_INCREMENT,
  `s_zone_id` INT NOT NULL,
  `row` VARCHAR(50) NULL,
  `number` INT NULL,
  `seat_booking_status` VARCHAR(50) NOT NULL DEFAULT 'available' COMMENT 'available | locked | reserved | sold — kept in sync with seat_lock_log / booking_seat / ticket',
  `seat_status` VARCHAR(50) NOT NULL COMMENT 'สถานะตัวที่นั่งเอง เช่น active | disabled | blocked (ไม่เกี่ยวกับการจอง)',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`s_id`),
  UNIQUE INDEX `uq_seat_zone_row_number` (`s_zone_id` ASC, `row` ASC, `number` ASC) VISIBLE,
  INDEX `idx_zone_booking_status` (`s_zone_id` ASC, `seat_booking_status` ASC) VISIBLE,
  CONSTRAINT `fk_seat_zone`
    FOREIGN KEY (`s_zone_id`)
    REFERENCES `concert_ticketing`.`zone` (`z_id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`booking_seat`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`booking_seat` (
  `bs_booking_id` INT NOT NULL,
  `bs_seat_id` INT NOT NULL,
  `seat_label_snapshot` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`bs_booking_id`, `bs_seat_id`),
  INDEX `fk_booking_seat_seat` (`bs_seat_id` ASC) VISIBLE,
  CONSTRAINT `fk_booking_seat_booking`
    FOREIGN KEY (`bs_booking_id`)
    REFERENCES `concert_ticketing`.`booking` (`b_id`),
  CONSTRAINT `fk_booking_seat_seat`
    FOREIGN KEY (`bs_seat_id`)
    REFERENCES `concert_ticketing`.`seat` (`s_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`payment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`payment` (
  `p_id` INT NOT NULL AUTO_INCREMENT,
  `p_booking_id` INT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `method` VARCHAR(50) NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `provider_transaction_id` VARCHAR(255) NULL DEFAULT NULL,
  `payment_metadata` JSON NULL DEFAULT NULL,
  PRIMARY KEY (`p_id`),
  UNIQUE INDEX `provider_transaction_id` (`provider_transaction_id` ASC) VISIBLE,
  INDEX `idx_payment_booking_id` (`p_booking_id` ASC) VISIBLE,
  CONSTRAINT `fk_payment_booking`
    FOREIGN KEY (`p_booking_id`)
    REFERENCES `concert_ticketing`.`booking` (`b_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`user_order`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`user_order` (
  `uo_id` INT NOT NULL AUTO_INCREMENT,
  `uo_booking_id` INT NOT NULL,
  `uo_payment_id` INT NOT NULL,
  `order_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_code` VARCHAR(100) NOT NULL,
  `concert_name_snapshot` VARCHAR(255) NOT NULL,
  `artist_snapshot` VARCHAR(255) NOT NULL,
  `venue_snapshot` VARCHAR(500) NOT NULL,
  `event_datetime_snapshot` DATETIME NOT NULL,
  `organization_name_snapshot` VARCHAR(255) NOT NULL,
  `user_firstname_snapshot` VARCHAR(100) NOT NULL,
  `user_lastname_snapshot` VARCHAR(100) NOT NULL,
  `user_phone_snapshot` VARCHAR(50) NULL,
  `user_email_snapshot` VARCHAR(255) NULL,
  `zone_name_snapshot` VARCHAR(255) NOT NULL,
  `unit_price_snapshot` DECIMAL(12,2) NOT NULL,
  `total_amount_snapshot` DECIMAL(12,2) NOT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`uo_id`),
  UNIQUE INDEX `booking_id` (`uo_booking_id` ASC) VISIBLE,
  UNIQUE INDEX `order_code` (`order_code` ASC) VISIBLE,
  UNIQUE INDEX `payment_id_UNIQUE` (`uo_payment_id` ASC) VISIBLE,
  CONSTRAINT `fk_order_booking`
    FOREIGN KEY (`uo_booking_id`)
    REFERENCES `concert_ticketing`.`booking` (`b_id`),
  CONSTRAINT `fk_order_payment`
    FOREIGN KEY (`uo_payment_id`)
    REFERENCES `concert_ticketing`.`payment` (`p_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`seat_lock_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`seat_lock_log` (
  `sll_id` INT NOT NULL AUTO_INCREMENT,
  `sll_booking_id` INT NOT NULL,
  `sll_seat_id` INT NOT NULL,
  `locked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `released_at` DATETIME NULL DEFAULT NULL,
  `sold_at` DATETIME NULL DEFAULT NULL,
  `active_seat_id` INT GENERATED ALWAYS AS (IF(`released_at` IS NULL AND `sold_at` IS NULL, `sll_seat_id`, NULL)) STORED,
  PRIMARY KEY (`sll_id`),
  INDEX `fk_lock_booking` (`sll_booking_id` ASC) VISIBLE,
  INDEX `fk_lock_seat` (`sll_seat_id` ASC) VISIBLE,
  INDEX `idx_lock_expiry` (`expires_at` ASC) VISIBLE,
  UNIQUE INDEX `uq_lock_active_seat` (`active_seat_id` ASC) VISIBLE,
  CONSTRAINT `fk_lock_booking`
    FOREIGN KEY (`sll_booking_id`)
    REFERENCES `concert_ticketing`.`booking` (`b_id`),
  CONSTRAINT `fk_lock_seat`
    FOREIGN KEY (`sll_seat_id`)
    REFERENCES `concert_ticketing`.`seat` (`s_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`staff_concert`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`staff_concert` (
  `sc_staff_id` INT NOT NULL,
  `sc_concert_id` INT NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` TINYINT NOT NULL,
  `revoked_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`sc_staff_id`, `sc_concert_id`),
  INDEX `fk_staff_concert_concert` (`sc_concert_id` ASC) VISIBLE,
  CONSTRAINT `fk_staff_concert_concert`
    FOREIGN KEY (`sc_concert_id`)
    REFERENCES `concert_ticketing`.`concert` (`c_id`),
  CONSTRAINT `fk_staff_concert_staff`
    FOREIGN KEY (`sc_staff_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`ticket`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`ticket` (
  `t_id` INT NOT NULL AUTO_INCREMENT,
  `t_order_id` INT NOT NULL,
  `t_zone_id` INT NOT NULL,
  `t_seat_id` INT NULL,
  `qr_code` VARCHAR(500) NOT NULL,
  `is_used` TINYINT NOT NULL DEFAULT '0',
  `ticket_code` VARCHAR(100) NOT NULL,
  `seat_label_snapshot` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` VARCHAR(20) NOT NULL DEFAULT 'valid' COMMENT 'valid | void | refunded',
  `voided_at` DATETIME NULL,
  `t_replaced_by_ticket_id` INT NULL,
  `active_seat_id` INT GENERATED ALWAYS AS (IF(`status` = 'valid', `t_seat_id`, NULL)) VIRTUAL,
  `slot_no` INT NOT NULL COMMENT 'ลำดับตั๋วในออเดอร์ 1..uo_quantity (ตั๋วที่ออกใหม่แทนใบเดิมใช้เลขเดิม)',
  `active_slot` INT GENERATED ALWAYS AS (IF(`status` = 'valid', `slot_no`, NULL)) STORED,
  PRIMARY KEY (`t_id`),
  UNIQUE INDEX `qr_code` (`qr_code` ASC) VISIBLE,
  UNIQUE INDEX `ticket_code` (`ticket_code` ASC) VISIBLE,
  INDEX `fk_ticket_order` (`t_order_id` ASC) VISIBLE,
  INDEX `fk_ticket_zone` (`t_zone_id` ASC) VISIBLE,
  INDEX `fk_ticket_seat_idx` (`t_seat_id` ASC) VISIBLE,
  UNIQUE INDEX `uq_ticket_active_seat` (`active_seat_id` ASC) VISIBLE,
  UNIQUE INDEX `fk_ticket_replaced_by_idx` (`t_replaced_by_ticket_id` ASC) VISIBLE,
  UNIQUE INDEX `uq_ticket_active_slot` (`t_order_id` ASC, `active_slot` ASC) VISIBLE,
  CONSTRAINT `fk_ticket_order`
    FOREIGN KEY (`t_order_id`)
    REFERENCES `concert_ticketing`.`user_order` (`uo_id`),
  CONSTRAINT `fk_ticket_seat`
    FOREIGN KEY (`t_seat_id`)
    REFERENCES `concert_ticketing`.`seat` (`s_id`),
  CONSTRAINT `fk_ticket_zone`
    FOREIGN KEY (`t_zone_id`)
    REFERENCES `concert_ticketing`.`zone` (`z_id`),
  CONSTRAINT `fk_ticket_replaced_by`
    FOREIGN KEY (`t_replaced_by_ticket_id`)
    REFERENCES `concert_ticketing`.`ticket` (`t_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`ticket_validation_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`ticket_validation_log` (
  `tvl_id` INT NOT NULL AUTO_INCREMENT,
  `tvl_ticket_id` INT NOT NULL,
  `tvl_staff_id` INT NOT NULL,
  `validation_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `result` TINYINT NOT NULL,
  PRIMARY KEY (`tvl_id`),
  INDEX `fk_validation_ticket` (`tvl_ticket_id` ASC) VISIBLE,
  INDEX `fk_validation_staff` (`tvl_staff_id` ASC) VISIBLE,
  CONSTRAINT `fk_validation_staff`
    FOREIGN KEY (`tvl_staff_id`)
    REFERENCES `concert_ticketing`.`app_user` (`u_id`),
  CONSTRAINT `fk_validation_ticket`
    FOREIGN KEY (`tvl_ticket_id`)
    REFERENCES `concert_ticketing`.`ticket` (`t_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `concert_ticketing`.`zone_inventory`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `concert_ticketing`.`zone_inventory` (
  `zi_zone_id` INT NOT NULL,
  `held` INT NOT NULL DEFAULT 0 COMMENT 'จำนวนที่ถูกล็อกไว้ รอชำระเงิน',
  `sold` INT NOT NULL DEFAULT 0 COMMENT 'จำนวนที่ชำระเงินแล้ว',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`zi_zone_id`),
  CONSTRAINT `fk_zone_inventory_zone`
    FOREIGN KEY (`zi_zone_id`)
    REFERENCES `concert_ticketing`.`zone` (`z_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci
COMMENT = 'ตัวนับที่นั่งของโซนยืน (GA) เท่านั้น: held = ล็อกรอจ่าย, sold = จ่ายแล้ว';


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
