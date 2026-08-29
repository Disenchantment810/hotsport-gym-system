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
?>
