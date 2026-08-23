<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['uid'])==0) {
	  // Return empty array or error
	  echo json_encode(array());
	  exit;
	}
	$today = date('Y-m-d H:i:s');
	$sql = "SELECT id, title, instructor, class_date, duration, capacity, price FROM tblclass WHERE class_date >= :today ORDER BY class_date";
	$query = $dbh->prepare($sql);
	$query->bindParam(':today', $today, PDO::PARAM_STR);
	$query->execute();
	$results = $query->fetchAll(PDO::FETCH_OBJ);
	$events = array();
	foreach($results as $result) {
		$events[] = array(
			'id' => $result->id,
			'title' => htmlentities($result->title),
			'start' => $result->class_date,
			// We can also set end time based on duration
			'end' => date('Y-m-d H:i:s', strtotime($result->class_date . ' + '.$result->duration.' minutes')),
			'instructor' => htmlentities($result->instructor),
			'duration' => $result->duration,
			'capacity' => $result->capacity,
			'price' => $result->price
		);
	}
	header('Content-Type: application/json');
	echo json_encode($events);
?>