<?php
session_start();
if (strlen($_SESSION['adminid'])==0) {
    header('location:logout.php');
} else {
    include('include/config.php');
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'get_member_data') {
    $member_id = isset($_POST['member_id']) && is_numeric($_POST['member_id']) ? intval($_POST['member_id']) : 0;

    if($member_id <= 0) {
        echo json_encode(array('error' => 'Invalid member ID'));
        exit;
    }

    // Get member info
    $sql_member = "SELECT * FROM tbluser WHERE id = ?";
    $query_member = $dbh->prepare($sql_member);
    $query_member->bindValue(1, $member_id);
    $query_member->execute();
    $member_info = $query_member->fetch(PDO::FETCH_ASSOC);

    if(!$member_info) {
        echo json_encode(array('error' => 'Member not found'));
        exit;
    }

    // Get goals summary
    $sql_active_goals = "SELECT COUNT(*) as count FROM tblgoals WHERE user_id = ? AND current_value < target_value";
    $query_active_goals = $dbh->prepare($sql_active_goals);
    $query_active_goals->bindValue(1, $member_id);
    $query_active_goals->execute();
    $active_goals = $query_active_goals->fetchColumn();

    $sql_completed_goals = "SELECT COUNT(*) as count FROM tblgoals WHERE user_id = ? AND current_value >= target_value";
    $query_completed_goals = $dbh->prepare($sql_completed_goals);
    $query_completed_goals->bindValue(1, $member_id);
    $query_completed_goals->execute();
    $completed_goals = $query_completed_goals->fetchColumn();

    // Get goals data for chart
    $sql_goals_chart = "SELECT
                            SUM(CASE WHEN g.current_value >= g.target_value THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN g.current_value < g.target_value THEN 1 ELSE 0 END) as in_progress
                        FROM tblgoals g WHERE user_id = ?";
    $query_goals_chart = $dbh->prepare($sql_goals_chart);
    $query_goals_chart->bindValue(1, $member_id);
    $query_goals_chart->execute();
    $goals_chart_result = $query_goals_chart->fetch(PDO::FETCH_ASSOC);

    // Get detailed goals list
    $sql_goals_list = "SELECT g.*, u.fname, u.lname, CONCAT(u.fname, ' ', u.lname) as member_name
                      FROM tblgoals g
                      INNER JOIN tbluser u ON g.user_id = u.id
                      WHERE g.user_id = ?
                      ORDER BY g.created_at DESC";
    $query_goals_list = $dbh->prepare($sql_goals_list);
    $query_goals_list->bindValue(1, $member_id);
    $query_goals_list->execute();
    $goals_list = $query_goals_list->fetchAll(PDO::FETCH_ASSOC);

    // Get workout summary
    $sql_workout_summary = "SELECT
                                COUNT(*) as total_workouts,
                                SUM(duration) as total_duration,
                                SUM(calories_burned) as total_calories
                            FROM tblworkouts WHERE user_id = ?";
    $query_workout_summary = $dbh->prepare($sql_workout_summary);
    $query_workout_summary->bindValue(1, $member_id);
    $query_workout_summary->execute();
    $workout_summary = $query_workout_summary->fetch(PDO::FETCH_ASSOC);
    // Handle null values
    $workout_summary['total_workouts'] = isset($workout_summary['total_workouts']) ? $workout_summary['total_workouts'] : 0;
    $workout_summary['total_duration'] = isset($workout_summary['total_duration']) ? $workout_summary['total_duration'] : 0;
    $workout_summary['total_calories'] = isset($workout_summary['total_calories']) ? $workout_summary['total_calories'] : 0;

    // Get workout frequency data for chart (last 7 days)
    $sql_workout_chart = "SELECT
                            DATE(w.workout_date) as workout_date,
                            COUNT(*) as count
                        FROM tblworkouts w WHERE user_id = ?
                        GROUP BY DATE(w.workout_date)
                        ORDER BY DATE(w.workout_date) DESC
                        LIMIT 7";
    $query_workout_chart = $dbh->prepare($sql_workout_chart);
    $query_workout_chart->bindValue(1, $member_id);
    $query_workout_chart->execute();
    $workout_chart_results = $query_workout_chart->fetchAll(PDO::FETCH_ASSOC);

    // Prepare workout chart data (reverse order for chronological display)
    $workout_labels = array();
    $workout_data = array();
    foreach(array_reverse($workout_chart_results) as $row) {
        $workout_labels[] = $row['workout_date'];
        $workout_data[] = (int)$row['count'];
    }

    // Get detailed workouts list
    $sql_workouts_list = "SELECT w.*, u.fname, u.lname, CONCAT(u.fname, ' ', u.lname) as member_name,
                         (SELECT COUNT(*) FROM tblworkout_exercises we WHERE we.workout_id = w.id) as exercise_count
                      FROM tblworkouts w
                      INNER JOIN tbluser u ON w.user_id = u.id
                      WHERE w.user_id = ?
                      ORDER BY w.workout_date DESC";
    $query_workouts_list = $dbh->prepare($sql_workouts_list);
    $query_workouts_list->bindValue(1, $member_id);
    $query_workouts_list->execute();
    $workouts_list = $query_workouts_list->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode(array(
        'member_info' => $member_info,
        'goals_summary' => array(
            'active_goals' => (int)$active_goals,
            'completed_goals' => (int)$completed_goals
        ),
        'goals_chart_data' => array(
            'completed' => (int)$goals_chart_result['completed'],
            'in_progress' => (int)$goals_chart_result['in_progress']
        ),
        'goals_list' => $goals_list,
        'workout_summary' => array(
            'total_workouts' => (int)$workout_summary['total_workouts'],
            'total_duration' => (int)$workout_summary['total_duration'],
            'total_calories' => (int)$workout_summary['total_calories']
        ),
        'workout_chart_data' => array(
            'labels' => $workout_labels,
            'data' => $workout_data
        ),
        'workouts_list' => $workouts_list
    ));
}
?>