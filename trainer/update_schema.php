<?php
require_once 'include/config.php';

$sql_trainers = "
CREATE TABLE IF NOT EXISTS `tbltrainers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(45) DEFAULT NULL,
  `password` varchar(100) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `bio` text,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_class_series = "
CREATE TABLE IF NOT EXISTS `tblclass_series` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `total_sessions` int(11) NOT NULL,
  `frequency` varchar(20) NOT NULL DEFAULT 'weekly' COMMENT 'daily, weekly, biweekly, monthly',
  `start_date` date NOT NULL,
  `capacity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Inactive',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`trainer_id`) REFERENCES `tbltrainers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_class_sessions = "
CREATE TABLE IF NOT EXISTS `tblclass_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `series_id` int(11) NOT NULL,
  `session_number` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_series_session` (`series_id`, `session_number`),
  FOREIGN KEY (`series_id`) REFERENCES `tblclass_series`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_class_enrollment = "
CREATE TABLE IF NOT EXISTS `tblclass_enrollment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `series_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `enrolled_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) NOT NULL DEFAULT 'enrolled' COMMENT 'enrolled, completed, withdrawn',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_series_user` (`series_id`, `user_id`),
  FOREIGN KEY (`series_id`) REFERENCES `tblclass_series`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_class_attendance = "
CREATE TABLE IF NOT EXISTS `tblclass_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `status` enum('attended','absent','late') NOT NULL DEFAULT 'absent',
  `marked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_enrollment` (`session_id`, `enrollment_id`),
  FOREIGN KEY (`session_id`) REFERENCES `tblclass_sessions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`enrollment_id`) REFERENCES `tblclass_enrollment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

$sql_certificates = "
CREATE TABLE IF NOT EXISTS `tblcertificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_code` varchar(50) NOT NULL,
  `series_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `completion_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Active, 0=Revoked',
  `issued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_code` (`certificate_code`),
  FOREIGN KEY (`series_id`) REFERENCES `tblclass_series`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `tbluser`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`trainer_id`) REFERENCES `tbltrainers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $dbh->exec($sql_trainers);
    echo "Table tbltrainers created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tbltrainers: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_class_series);
    echo "Table tblclass_series created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblclass_series: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_class_sessions);
    echo "Table tblclass_sessions created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblclass_sessions: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_class_enrollment);
    echo "Table tblclass_enrollment created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblclass_enrollment: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_class_attendance);
    echo "Table tblclass_attendance created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblclass_attendance: " . $e->getMessage() . "\n";
}

try {
    $dbh->exec($sql_certificates);
    echo "Table tblcertificates created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating tblcertificates: " . $e->getMessage() . "\n";
}

// Seed a default test trainer (password: trainer123) if none exists.
$check = $dbh->query("SELECT COUNT(*) AS c FROM tbltrainers");
$row = $check->fetch(PDO::FETCH_OBJ);
if ($row->c == 0) {
    $seed = $dbh->prepare("INSERT INTO tbltrainers (name, email, mobile, password, specialization, bio) VALUES (:name, :email, :mobile, :password, :specialization, :bio)");
    $seed->bindValue(':name', 'Test Trainer', PDO::PARAM_STR);
    $seed->bindValue(':email', 'trainer@hotsport.com', PDO::PARAM_STR);
    $seed->bindValue(':mobile', '0700000000', PDO::PARAM_STR);
    $seed->bindValue(':password', md5('trainer123'), PDO::PARAM_STR);
    $seed->bindValue(':specialization', 'Strength & Conditioning', PDO::PARAM_STR);
    $seed->bindValue(':bio', 'Default test trainer account.', PDO::PARAM_STR);
    $seed->execute();
    echo "Seeded default trainer (trainer@hotsport.com / trainer123).\n";
}
?>
