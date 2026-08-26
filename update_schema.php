<?php
require_once 'include/config.php';

$sql_measurements = "
CREATE TABLE IF NOT EXISTS `tblmeasurements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `body_fat` decimal(5,2) DEFAULT NULL,
  `chest` decimal(5,2) DEFAULT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `hips` decimal(5,2) DEFAULT NULL,
  `thigh_left` decimal(5,2) DEFAULT NULL,
  `thigh_right` decimal(5,2) DEFAULT NULL,
  `bicep_left` decimal(5,2) DEFAULT NULL,
  `bicep_right` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_goals = "
CREATE TABLE IF NOT EXISTS `tblgoals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goal_type` varchar(50) NOT NULL COMMENT 'e.g., weight, body_fat, chest, etc.',
  `target_value` decimal(5,2) NOT NULL,
  `target_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $dbh->exec($sql_measurements);
    echo "Table tblmeasurements created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblmeasurements: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_goals);
    echo "Table tblgoals created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblgoals: " . $e->getMessage() . "\n";
}
?>