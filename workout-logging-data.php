<?php
session_start();
if(strlen($_SESSION["uid"])==0) {
    header('location:login.php');
} else {
    include('include/config.php');
}
$user_id = $_SESSION["uid"];

$action = isset($_POST['action']) ? $_POST['action'] : '';

if($action == 'get_workouts') {
    // Get user's workouts
    $sql = "SELECT w.*,
                   (SELECT COUNT(*) FROM tblworkout_exercises we WHERE we.workout_id = w.id) as exercise_count
            FROM tblworkouts w
            WHERE w.user_id = ?
            ORDER BY w.workout_date DESC, w.created_at DESC";
    $query = $dbh->prepare($sql);
    $query->bindValue(1, $user_id);
    $query->execute();
    $workouts_list = $query->fetchAll(PDO::FETCH_ASSOC);

    // Get workout chart data (last 7 days)
    $sql_chart = "SELECT
                      DATE(w.workout_date) as workout_date,
                      COUNT(*) as count
                  FROM tblworkouts w
                  WHERE w.user_id = ?
                  GROUP BY DATE(w.workout_date)
                  ORDER BY DATE(w.workout_date) DESC
                  LIMIT 7";
    $query_chart = $dbh->prepare($sql_chart);
    $query_chart->bindValue(1, $user_id);
    $query_chart->execute();
    $chart_results = $query_chart->fetchAll(PDO::FETCH_ASSOC);

    // Prepare chart data (reverse for chronological order)
    $labels = array();
    $data = array();
    foreach(array_reverse($chart_results) as $row) {
        $labels[] = $row['workout_date'];
        $data[] = (int)$row['count'];
    }

    echo json_encode(array(
        'workouts_list' => $workouts_list,
        'workout_chart_data' => array(
            'labels' => $labels,
            'data' => $data
        )
    ));
}

if($action == 'add_workout') {
    $workout_date = isset($_POST['workout_date']) ? $_POST['workout_date'] : '';
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;
    $calories_burned = isset($_POST['calories_burned']) ? intval($_POST['calories_burned']) : 0;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $exercises = isset($_POST['exercises']) ? $_POST['exercises'] : array();

    // Validation
    if(empty($workout_date) || $duration <= 0 || $calories_burned < 0 || empty($exercises)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid workout data'));
        exit;
    }

    try {
        // Start transaction
        $dbh->beginTransaction();

        // Insert workout
        $sql_workout = "INSERT INTO tblworkouts (user_id, workout_date, duration, calories_burned, notes)
                        VALUES (?, ?, ?, ?, ?)";
        $query_workout = $dbh->prepare($sql_workout);
        $query_workout->bindValue(1, $user_id);
        $query_workout->bindValue(2, $workout_date);
        $query_workout->bindValue(3, $duration);
        $query_workout->bindValue(4, $calories_burned);
        $query_workout->bindValue(5, $notes);
        $query_workout->execute();
        $workout_id = $dbh->lastInsertId();

        // Insert exercises
        $sql_exercise = "INSERT INTO tblworkout_exercises (workout_id, exercise_name, sets, reps, weight)
                         VALUES (?, ?, ?, ?, ?)";
        $query_exercise = $dbh->prepare($sql_exercise);

        foreach($exercises as $exercise) {
            $query_exercise->bindValue(1, $workout_id);
            $query_exercise->bindValue(2, $exercise['name']);
            $query_exercise->bindValue(3, $exercise['sets']);
            $query_exercise->bindValue(4, $exercise['reps']);
            $weight = (isset($exercise['weight']) && is_numeric($exercise['weight'])) ? floatval($exercise['weight']) : 0;
            $query_exercise->bindValue(5, $weight);
            $query_exercise->execute();
        }

        // Commit transaction
        $dbh->commit();

        echo json_encode(array('success' => true, 'message' => 'Workout logged successfully'));
    } catch(PDOException $e) {
        // Rollback transaction on error
        $dbh->rollBack();
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()));
    }
}

if($action == 'get_workout_details') {
    $workout_id = isset($_POST['workout_id']) ? intval($_POST['workout_id']) : 0;

    if($workout_id <= 0) {
        echo json_encode(array('success' => false, 'message' => 'Invalid workout ID'));
        exit;
    }

    try {
        // Get workout info
        $sql_workout = "SELECT w.*, u.fname, u.lname
                        FROM tblworkouts w
                        INNER JOIN tbluser u ON w.user_id = u.id
                        WHERE w.id = ? AND w.user_id = ?";
        $query_workout = $dbh->prepare($sql_workout);
        $query_workout->bindValue(1, $workout_id);
        $query_workout->bindValue(2, $user_id);
        $query_workout->execute();
        $workout = $query_workout->fetch(PDO::FETCH_ASSOC);

        if(!$workout) {
            echo json_encode(array('success' => false, 'message' => 'Workout not found or unauthorized'));
            exit;
        }

        // Get exercises for this workout
        $sql_exercises = "SELECT * FROM tblworkout_exercises WHERE workout_id = ?";
        $query_exercises = $dbh->prepare($sql_exercises);
        $query_exercises->bindValue(1, $workout_id);
        $query_exercises->execute();
        $exercises = $query_exercises->fetchAll(PDO::FETCH_ASSOC);

        $workout['exercises'] = $exercises;

        echo json_encode(array(
            'success' => true,
            'workout' => $workout
        ));
    } catch(PDOException $e) {
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . $e->getMessage()));
    }
}
?>