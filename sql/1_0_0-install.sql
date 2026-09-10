--
-- Lab Order Queue Module v1.0.0
-- Install script
--

CREATE TABLE IF NOT EXISTS `lab_order_queue` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `procedure_order_id` BIGINT(20) NOT NULL COMMENT 'FK to procedure_order.procedure_order_id',
    `patient_id` BIGINT(20) NOT NULL,
    `provider_id` BIGINT(20) NOT NULL,
    `status` ENUM('pending', 'attached', 'cancelled') NOT NULL DEFAULT 'pending',
    `encounter_id` BIGINT(20) DEFAULT NULL COMMENT 'Set when attached to encounter',
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `attached_at` DATETIME DEFAULT NULL,
    `cancelled_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_patient_status` (`patient_id`, `status`),
    KEY `idx_procedure_order` (`procedure_order_id`)
) ENGINE=InnoDB COMMENT='Tracks pre-encounter lab orders awaiting encounter attachment';
