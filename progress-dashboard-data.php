<?php
session_start();
if(strlen($_SESSION["uid"])==0) {
    header('location:login.php');
} else {
    include('include/config.php');
}
$user_id = $_SESSION["uid"];

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'get_dashboard_data') {
    // Get overview data
    $sql_overview = "SELECT
                        (SELECT COUNT(*) FROM tblgoals WHERE user_id = ? AND current_value < target_value) as active_goals,
                        (SELECT COUNT(*) FROM tblgoals WHERE user_id = ? AND current_value >= target_value) as completed_goals,
                        (SELECT COUNT(*) FROM tblworkouts WHERE user_id = ?) as total_workouts,
                        (SELECT AVG(duration) FROM tblworkouts WHERE user_id = ?) as avg_duration
                    FROM DUAL";
    $query_overview = $dbh->prepare($sql_overview);
    $query_overview->bindValue(1, $user_id);
    $query_overview->bindValue(2, $user_id);
    $query_overview->bindValue(3, $user_id);
    $query_overview->bindValue(4, $user_id);
    $query_overview->execute();
    $overview = $query_overview->fetch(PDO::FETCH_ASSOC);
    // Handle null values
    $overview['active_goals'] = isset($overview['active_goals']) ? $overview['active_goals'] : 0;
    $overview['completed_goals'] = isset($overview['completed_goals']) ? $overview['completed_goals'] : 0;
    $overview['total_workouts'] = isset($overview['total_workouts']) ? $overview['total_workouts'] : 0;
    $overview['avg_duration'] = round(isset($overview['avg_duration']) ? $overview['avg_duration'] : 0, 1);

    // Get goals chart data (completed vs in progress)
    $sql_goals_chart = "SELECT
                            SUM(CASE WHEN current_value >= target_value THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN current_value < target_value THEN 1 ELSE 0 END) as in_progress
                        FROM tblgoals WHERE user_id = ?";
    $query_goals_chart = $dbh->prepare($sql_goals_chart);
    $query_goals_chart->bindValue(1, $user_id);
    $query_goals_chart->execute();
    $goals_chart_data = $query_goals_chart->fetch(PDO::FETCH_ASSOC);

    // Get recent goals (limit 5)
    $sql_recent_goals = "SELECT * FROM tblgoals WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $query_recent_goals = $dbh->prepare($sql_recent_goals);
    $query_recent_goals->bindValue(1, $user_id);
    $query_recent_goals->execute();
    $recent_goals = $query_recent_goals->fetchAll(PDO::FETCH_ASSOC);

    // Get workout chart data (last 7 days)
    $sql_workout_chart = "SELECT
                            DATE(w.workout_date) as workout_date,
                            COUNT(*) as count
                        FROM tblworkouts w
                        WHERE w.user_id = ?
                        GROUP BY DATE(w.workout_date)
                        ORDER BY DATE(w.workout_date) DESC
                        LIMIT 7";
    $query_workout_chart = $dbh->prepare($sql_workout_chart);
    $query_workout_chart->bindValue(1, $user_id);
    $query_workout_chart->execute();
    $workout_chart_results = $query_workout_chart->fetchAll(PDO::FETCH_ASSOC);

    // Prepare workout chart data (reverse for chronological order)
    $workout_labels = array();
    $workout_data = array();
    foreach(array_reverse($workout_chart_results) as $row) {
        $workout_labels[] = $row['workout_date'];
        $workout_data[] = (int)$row['count'];
    }

    // Get recent workouts (limit 5)
    $sql_recent_workouts = "SELECT w.*,
                               (SELECT COUNT(*) FROM tblworkout_exercises we WHERE we.workout_id = w.id) as exercise_count
                        FROM tblworkouts w
                        WHERE w.user_id = ?
                        ORDER BY w.workout_date DESC, w.created_at DESC
                        LIMIT 5";
    $query_recent_workouts = $dbh->prepare($sql_recent_workouts);
    $query_recent_workouts->bindValue(1, $user_id);
    $query_recent_workouts->execute();
    $recent_workouts = $query_recent_workouts->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array(
        'overview' => $overview,
        'goals_chart_data' => array(
            'completed' => (int)$goals_chart_data['completed'],
            'in_progress' => (int)$goals_chart_data['in_progress']
        ),
        'recent_goals' => $recent_goals,
        'workout_chart_data' => array(
            'labels' => $workout_labels,
            'data' => $workout_data
        ),
        'recent_workouts' => $recent_workouts
    ));
}
?>