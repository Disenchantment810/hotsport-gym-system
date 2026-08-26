<?php
session_start();
if (strlen($_SESSION['adminid'])==0) {
    header('location:logout.php');
} else {
    include('include/config.php');
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'get_summary') {
    $from_date = isset($_POST['from_date']) && !empty($_POST['from_date']) ? $_POST['from_date'] : '';
    $to_date = isset($_POST['to_date']) && !empty($_POST['to_date']) ? $_POST['to_date'] : '';
    $member_id = isset($_POST['member_id']) && !empty($_POST['member_id']) ? $_POST['member_id'] : '';

    $where = "";
    $params = array();

    if(!empty($from_date) && !empty($to_date)) {
        $where .= " AND DATE(w.workout_date) BETWEEN ? AND ?";
        $params[] = $from_date;
        $params[] = $to_date;
    }
    if(!empty($member_id)) {
        $where .= " AND w.user_id = ?";
        $params[] = $member_id;
    }

    // Get goals summary
    $goals_where = "";
    $goals_params = array();
    if(!empty($member_id)) {
        $goals_where .= " AND g.user_id = ?";
        $goals_params[] = $member_id;
    }

    // Active goals (not completed)
    $sql_active = "SELECT COUNT(*) as count FROM tblgoals g WHERE 1=1 $goals_where AND g.current_value < g.target_value";
    $query_active = $dbh->prepare($sql_active);
    if(!empty($goals_params)) {
        foreach($goals_params as $i => $param) {
            $query_active->bindValue(($i+1), $param);
        }
    }
    $query_active->execute();
    $active_goals = $query_active->fetchColumn();

    // Completed goals
    $sql_completed = "SELECT COUNT(*) as count FROM tblgoals g WHERE 1=1 $goals_where AND g.current_value >= g.target_value";
    $query_completed = $dbh->prepare($sql_completed);
    if(!empty($goals_params)) {
        foreach($goals_params as $i => $param) {
            $query_completed->bindValue(($i+1), $param);
        }
    }
    $query_completed->execute();
    $completed_goals = $query_completed->fetchColumn();

    // Total workouts
    $sql_workouts = "SELECT COUNT(*) as count FROM tblworkouts w WHERE 1=1 $where";
    $query_workouts = $dbh->prepare($sql_workouts);
    if(!empty($params)) {
        foreach($params as $i => $param) {
            $query_workouts->bindValue(($i+1), $param);
        }
    }
    $query_workouts->execute();
    $total_workouts = $query_workouts->fetchColumn();

    // Goals data for chart
    $sql_goals_chart = "SELECT
                            SUM(CASE WHEN g.current_value >= g.target_value THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN g.current_value < g.target_value THEN 1 ELSE 0 END) as in_progress
                        FROM tblgoals g WHERE 1=1 $goals_where";
    $query_goals_chart = $dbh->prepare($sql_goals_chart);
    if(!empty($goals_params)) {
        foreach($goals_params as $i => $param) {
            $query_goals_chart->bindValue(($i+1), $param);
        }
    }
    $query_goals_chart->execute();
    $goals_result = $query_goals_chart->fetch(PDO::FETCH_ASSOC);

    // Workout frequency data (last 7 days)
    $sql_workout_chart = "SELECT
                            DATE(w.workout_date) as workout_date,
                            COUNT(*) as count
                        FROM tblworkouts w WHERE 1=1 $where
                        GROUP BY DATE(w.workout_date)
                        ORDER BY DATE(w.workout_date) DESC
                        LIMIT 7";
    $query_workout_chart = $dbh->prepare($sql_workout_chart);
    if(!empty($params)) {
        foreach($params as $i => $param) {
            $query_workout_chart->bindValue(($i+1), $param);
        }
    }
    $query_workout_chart->execute();
    $workout_results = $query_workout_chart->fetchAll(PDO::FETCH_ASSOC);

    // Prepare workout chart data (reverse order for chronological display)
    $workout_labels = array();
    $workout_data = array();
    foreach(array_reverse($workout_results) as $row) {
        $workout_labels[] = $row['workout_date'];
        $workout_data[] = (int)$row['count'];
    }

    // Get detailed goals list
    $sql_goals_list = "SELECT g.*, u.fname, u.lname, CONCAT(u.fname, ' ', u.lname) as member_name
                      FROM tblgoals g
                      INNER JOIN tbluser u ON g.user_id = u.id
                      WHERE 1=1 $goals_where
                      ORDER BY g.created_at DESC
                      LIMIT 20";
    $query_goals_list = $dbh->prepare($sql_goals_list);
    if(!empty($goals_params)) {
        foreach($goals_params as $i => $param) {
            $query_goals_list->bindValue(($i+1), $param);
        }
    }
    $query_goals_list->execute();
    $goals_list = $query_goals_list->fetchAll(PDO::FETCH_ASSOC);

    // Get detailed workouts list
    $sql_workouts_list = "SELECT w.*, u.fname, u.lname, CONCAT(u.fname, ' ', u.lname) as member_name,
                         (SELECT COUNT(*) FROM tblworkout_exercises we WHERE we.workout_id = w.id) as exercise_count
                      FROM tblworkouts w
                      INNER JOIN tbluser u ON w.user_id = u.id
                      WHERE 1=1 $where
                      ORDER BY w.workout_date DESC
                      LIMIT 20";
    $query_workouts_list = $dbh->prepare($sql_workouts_list);
    if(!empty($params)) {
        foreach($params as $i => $param) {
            $query_workouts_list->bindValue(($i+1), $param);
        }
    }
    $query_workouts_list->execute();
    $workouts_list = $query_workouts_list->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode(array(
        'active_goals' => (int)$active_goals,
        'completed_goals' => (int)$completed_goals,
        'total_workouts' => (int)$total_workouts,
        'goals_data' => array(
            'completed' => (int)$goals_result['completed'],
            'in_progress' => (int)$goals_result['in_progress']
        ),
        'workout_data' => array(
            'labels' => $workout_labels,
            'data' => $workout_data
        ),
        'goals_list' => $goals_list,
        'workouts_list' => $workouts_list
    ));
}
?>