<?php
require_once 'include/config.php';

$sql_announcements = "
CREATE TABLE IF NOT EXISTS `tblannouncements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'General' COMMENT 'General, Important, Urgent, Event',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Archived',
  `created_by` varchar(100) DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_announcement_reads = "
CREATE TABLE IF NOT EXISTS `tblannouncement_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_announcement` (`announcement_id`, `user_id`),
  FOREIGN KEY (`announcement_id`) REFERENCES `tblannouncements`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

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
  `goal_type` varchar(50) NOT NULL COMMENT 'e.g., weight, chest, workout_frequency',
  `target_value` decimal(10,2) NOT NULL,
  `current_value` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(20) NOT NULL COMMENT 'e.g., kg, cm, times/week',
  `target_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_workouts = "
CREATE TABLE IF NOT EXISTS `tblworkouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `workout_date` date NOT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'minutes',
  `calories_burned` int(11) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_exercises = "
CREATE TABLE IF NOT EXISTS `tblworkout_exercises` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workout_id` int(11) NOT NULL,
  `exercise_name` varchar(100) NOT NULL,
  `sets` int(11) NOT NULL,
  `reps` int(11) NOT NULL,
  `weight` decimal(10,2) DEFAULT NULL COMMENT 'weight used in kg/lbs',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`workout_id`) REFERENCES `tblworkouts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $dbh->exec($sql_announcements);
    echo "Table tblannouncements created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblannouncements: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_announcement_reads);
    echo "Table tblannouncement_reads created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblannouncement_reads: " . $e->getMessage() . "\n";
}

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

try {
    $dbh->exec($sql_workouts);
    echo "Table tblworkouts created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblworkouts: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_exercises);
    echo "Table tblworkout_exercises created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblworkout_exercises: " . $e->getMessage() . "\n";
}

// Add is_deleted column to tbladdpackage (idempotent) for soft-delete support
$col = $dbh->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbladdpackage' AND COLUMN_NAME = 'is_deleted'");
$exists = $col->fetch(PDO::FETCH_OBJ)->c;
if ($exists == 0) {
    $dbh->exec("ALTER TABLE `tbladdpackage`
        ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=active, 1=deleted'");
    echo "Added is_deleted column to tbladdpackage.\n";
} else {
    echo "is_deleted column already exists.\n";
}
?>