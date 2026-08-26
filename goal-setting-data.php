<?php
session_start();
if(strlen($_SESSION["uid"])==0) {
    header('location:login.php');
} else {
    include('include/config.php');
}
$user_id = $_SESSION["uid"];

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'get_goals') {
    // Get user's goals
    $sql = "SELECT * FROM tblgoals WHERE user_id = ? ORDER BY created_at DESC";
    $query = $dbh->prepare($sql);
    $query->bindValue(1, $user_id);
    $query->execute();
    $goals_list = $query->fetchAll(PDO::FETCH_ASSOC);

    // Get goals chart data (completed vs in progress)
    $sql_chart = "SELECT
                      SUM(CASE WHEN current_value >= target_value THEN 1 ELSE 0 END) as completed,
                      SUM(CASE WHEN current_value < target_value THEN 1 ELSE 0 END) as in_progress
                  FROM tblgoals WHERE user_id = ?";
    $query_chart = $dbh->prepare($sql_chart);
    $query_chart->bindValue(1, $user_id);
    $query_chart->execute();
    $goals_chart_data = $query_chart->fetch(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'goals_list' => $goals_list,
        'goals_chart_data' => array(
            'completed' => (int)$goals_chart_data['completed'],
            'in_progress' => (int)$goals_chart_data['in_progress']
        )
    ));
}

if($action == 'add_goal') {
    $goal_type = isset($_POST['goal_type']) ? $_POST['goal_type'] : '';
    $target_value = isset($_POST['target_value']) ? $_POST['target_value'] : '';
    $unit = isset($_POST['unit']) ? $_POST['unit'] : '';
    $target_date = isset($_POST['target_date']) ? $_POST['target_date'] : '';

    // Validation
    if(empty($goal_type) || empty($target_value) || empty($unit) || empty($target_date)) {
        echo json_encode(array('success' => false, 'message' => 'All fields are required'));
        exit;
    }

    if(!is_numeric($target_value) || floatval($target_value) <= 0) {
        echo json_encode(array('success' => false, 'message' => 'Target value must be a positive number'));
        exit;
    }

    try {
        $sql = "INSERT INTO tblgoals (user_id, goal_type, target_value, current_value, unit, target_date)
                VALUES (?, ?, ?, 0.00, ?, ?)";
        $query = $dbh->prepare($sql);
        $query->bindValue(1, $user_id);
        $query->bindValue(2, $goal_type);
        $query->bindValue(3, $target_value);
        $query->bindValue(4, $unit);
        $query->bindValue(5, $target_date);
        $query->execute();

        echo json_encode(array('success' => true, 'message' => 'Goal added successfully'));
    } catch(PDOException $e) {
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()));
    }
}

if($action == 'update_goal_progress') {
    $goal_id = isset($_POST['goal_id']) ? intval($_POST['goal_id']) : 0;
    $current_value = isset($_POST['current_value']) ? $_POST['current_value'] : '';

    if($goal_id <= 0 || !is_numeric($current_value) || floatval($current_value) < 0) {
        echo json_encode(array('success' => false, 'message' => 'Invalid goal ID or current value'));
        exit;
    }

    try {
        $sql = "UPDATE tblgoals SET current_value = ? WHERE id = ? AND user_id = ?";
        $query = $dbh->prepare($sql);
        $query->bindValue(1, $current_value);
        $query->bindValue(2, $goal_id);
        $query->bindValue(3, $user_id);
        $query->execute();

        if($query->rowCount() > 0) {
            echo json_encode(array('success' => true, 'message' => 'Goal progress updated'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Goal not found or unauthorized'));
        }
    } catch(PDOException $e) {
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()));
    }
}

if($action == 'delete_goal') {
    $goal_id = isset($_POST['goal_id']) ? intval($_POST['goal_id']) : 0;

    if($goal_id <= 0) {
        echo json_encode(array('success' => false, 'message' => 'Invalid goal ID'));
        exit;
    }

    try {
        $sql = "DELETE FROM tblgoals WHERE id = ? AND user_id = ?";
        $query = $dbh->prepare($sql);
        $query->bindValue(1, $goal_id);
        $query->bindValue(2, $user_id);
        $query->execute();

        if($query->rowCount() > 0) {
            echo json_encode(array('success' => true, 'message' => 'Goal deleted'));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Goal not found or unauthorized'));
        }
    } catch(PDOException $e) {
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()));
    }
}
?>