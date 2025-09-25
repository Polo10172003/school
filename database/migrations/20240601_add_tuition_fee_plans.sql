-- Migration: introduce pricing variants and plan-level details for tuition fees
-- Run inside the `student_enrollmentform` database

ALTER TABLE `tuition_fees`
  ADD COLUMN `pricing_category` VARCHAR(50) NOT NULL DEFAULT 'regular' AFTER `student_type`;

CREATE TABLE IF NOT EXISTS `tuition_fee_plans` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tuition_fee_id` INT NOT NULL,
  `plan_type` ENUM('annually','cash','semi_annual','quarterly','monthly') NOT NULL,
  `due_upon_enrollment` DECIMAL(10,2) NOT NULL,
  `next_payment_breakdown` TEXT NULL,
  `notes` TEXT NULL,
  `base_snapshot` TEXT NULL,
  `display_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_plan_per_fee` (`tuition_fee_id`, `plan_type`),
  CONSTRAINT `fk_plan_fee` FOREIGN KEY (`tuition_fee_id`) REFERENCES `tuition_fees`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: make existing rows explicit regular pricing
UPDATE `tuition_fees`
SET `pricing_category` = 'regular'
WHERE `pricing_category` IS NULL OR `pricing_category` = '';
