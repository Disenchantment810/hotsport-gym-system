<?php
// ============================================================
// M-Pesa enrollment payments schema (idempotent)
// Run via: http://localhost/Hotsport/mpesa/update_schema.php
// ============================================================
require_once __DIR__ . '/../include/config.php';

// 1. Create tblclass_enrollment_payments
$sql = "
CREATE TABLE IF NOT EXISTS `tblclass_enrollment_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `checkout_request_id` varchar(100) DEFAULT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `transaction_receipt` varchar(100) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `result_code` varchar(10) DEFAULT NULL,
  `result_desc` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','SUCCESS','FAILED','TIMEOUT') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_request_id` (`checkout_request_id`),
  KEY `enrollment_id` (`enrollment_id`),
  CONSTRAINT `fk_enroll_pay_enrollment` FOREIGN KEY (`enrollment_id`)
    REFERENCES `tblclass_enrollment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";
$dbh->exec($sql);
echo "Table tblclass_enrollment_payments ready.\n";

// 2. Add payment_status column to tblclass_enrollment (idempotent)
$col = $dbh->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblclass_enrollment' AND COLUMN_NAME = 'payment_status'");
$exists = $col->fetch(PDO::FETCH_OBJ)->c;
if ($exists == 0) {
    $dbh->exec("ALTER TABLE `tblclass_enrollment`
        ADD COLUMN `payment_status` varchar(20) NOT NULL DEFAULT 'pending'
        COMMENT 'pending, paid, failed' AFTER `status`");
    echo "Added payment_status column to tblclass_enrollment.\n";
} else {
    echo "payment_status column already exists.\n";
}

// 3. Create tblsubscriptions (package subscriptions, replaces tblbooking)
$sql = "
CREATE TABLE IF NOT EXISTS `tblsubscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, active, expired, cancelled',
  `payment_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, paid, failed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_sub_package` FOREIGN KEY (`package_id`)
    REFERENCES `tbladdpackage`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`)
    REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";
$dbh->exec($sql);
echo "Table tblsubscriptions ready.\n";

// 4. Create tblpayments (unified M-Pesa payment ledger)
$sql = "
CREATE TABLE IF NOT EXISTS `tblpayments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `payment_type` enum('package','class_series') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `checkout_request_id` varchar(100) DEFAULT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `transaction_receipt` varchar(100) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `result_code` varchar(10) DEFAULT NULL,
  `result_desc` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','SUCCESS','FAILED','TIMEOUT') NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_request_id` (`checkout_request_id`),
  KEY `user_id` (`user_id`),
  KEY `payment_type_ref` (`payment_type`,`reference_id`),
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`)
    REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";
$dbh->exec($sql);
echo "Table tblpayments ready.\n";
?>
