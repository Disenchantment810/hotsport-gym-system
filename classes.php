<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['uid'])==0) {
	  header('location:login.php');
	  }
	?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>User | Classes</title>
	<meta charset="UTF-8">
	<meta name="description" content="Ahana Yoga HTML Template">
	<meta name="keywords" content="yoga, html">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Stylesheets -->
	<link rel="stylesheet" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" href="css/font-awesome.min.css"/>
	<link rel="stylesheet" href="css/owl.carousel.min.css"/>
	<link rel="stylesheet" href="css/nice-select.css"/>
	<link rel="stylesheet" href="css/slicknav.min.css"/>

	<!-- Main Stylesheets -->
	<link rel="stylesheet" href="css/style.css"/>

	<!-- FullCalendar CSS (from admin directory)-->
	<link rel="stylesheet" type="text/css" href="../admin/css/main.css">
</head>
<body>
	<!-- Page Preloder -->

	<!-- Header Section -->
	<?php include 'include/header.php';?>
	<!-- Header Section end -->

	<!-- Page top Section -->
	<section class="page-top-section set-bg" data-setbg="img/page-top-bg.jpg">
		<div class="container">
			<div class="row">
				<div class="col-lg-7 m-auto text-white">
					<h2>Available Classes</h2>
				</div>
			</div>
		</div>
	</section>
	<!-- Page top Section end -->

	<!-- Contact Section -->
	<section class="contact-page-section spad overflow-hidden">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="tile">
						<div class="tile-body">
							<!-- Calendar -->
							<div id="calendar" style="max-width: 800px; margin: 0 auto;"></div>
							<hr />
							<!-- List of Classes -->
							<h3>Upcoming Classes</h3>
							<div class="table-responsive">
								<table class="table table-hover table-bordered" id="classTable">
									<thead>
										<tr>
											<th>Title</th>
											<th>Instructor</th>
											<th>Date & Time</th>
											<th>Duration</th>
											<th>Capacity</th>
											<th>Price</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										// Handle booking form submission
										if(isset($_POST['book_class'])) {
											$class_id = intval($_POST['class_id']);
											$user_id = intval($_SESSION['uid']);

											// Check if user already booked this class
											$sql_check = "SELECT * FROM tblclassbooking WHERE class_id = :class_id AND userid = :user_id";
											$query_check = $dbh->prepare($sql_check);
											$query_check->bindParam(':class_id', $class_id, PDO::PARAM_INT);
											$query_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
											$query_check->execute();
											if($query_check->rowCount() > 0) {
												echo '<div class="alert alert-danger">You have already booked this class.</div>';
											} else {
												// Check class capacity
												$sql_class = "SELECT capacity FROM tblclass WHERE id = :class_id";
												$query_class = $dbh->prepare($sql_class);
												$query_class->bindParam(':class_id', $class_id, PDO::PARAM_INT);
												$query_class->execute();
												$class = $query_class->fetch(PDO::FETCH_OBJ);
												if($class) {
													// Count current bookings for this class
													$sql_count = "SELECT COUNT(*) as count FROM tblclassbooking WHERE class_id = :class_id";
													$query_count = $dbh->prepare($sql_count);
													$query_count->bindParam(':class_id', $class_id, PDO::PARAM_INT);
													$query_count->execute();
													$count_result = $query_count->fetch(PDO::FETCH_OBJ);
													if($count_result->count >= $class->capacity) {
														echo '<div class="alert alert-danger">This class is fully booked.</div>';
													} else {
														// Insert booking
														$sql_insert = "INSERT INTO tblclassbooking (class_id, userid) VALUES (:class_id, :userid)";
														$query_insert = $dbh->prepare($sql_insert);
														$query_insert->bindParam(':class_id', $class_id, PDO::PARAM_INT);
														$query_insert->bindParam(':userid', $user_id, PDO::PARAM_INT);
														$query_insert->execute();
														if($dbh->lastInsertId() > 0) {
															echo '<div class="alert alert-success">Class booked successfully!</div>';
															// Redirect to avoid resubmission
															echo '<script>window.location.href="classes.php"</script>';
														} else {
															echo '<div class="alert alert-danger">Booking failed. Please try again.</div>';
														}
													}
												}
											}
										}
										?>
										<?php
										$today = date('Y-m-d H:i:s');
										$sql = "SELECT * FROM tblclass WHERE class_date >= :today ORDER BY class_date";
										$query = $dbh->prepare($sql);
										$query->bindParam(':today', $today, PDO::PARAM_STR);
										$query->execute();
										$results = $query->fetchAll(PDO::FETCH_OBJ);
										if($query->rowCount() > 0) {
											foreach($results as $result) {
												?>
												<tr>
													<td><?php echo htmlentities($result->title);?></td>
													<td><?php echo htmlentities($result->instructor);?></td>
													<td><?php echo htmlentities($result->class_date);?></td>
													<td><?php echo htmlentities($result->duration);?></td>
													<td><?php echo htmlentities($result->capacity);?></td>
													<td>$<?php echo number_format($result->price, 2);?></td>
													<td>
														<form method="post" style="display: inline;">
															<input type="hidden" name="class_id" value="<?php echo $result->id;?>">
															<button type="submit" name="book_class" class="btn btn-sm btn-primary">Book</button>
														</form>
														<?php
														// Check if user already booked this class
														$user_id = intval($_SESSION['uid']);
														$sql_user_check = "SELECT * FROM tblclassbooking WHERE class_id = :class_id AND userid = :user_id";
														$query_user_check = $dbh->prepare($sql_user_check);
														$query_user_check->bindParam(':class_id', $result->id, PDO::PARAM_INT);
														$query_user_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
														$query_user_check->execute();
														if($query_user_check->rowCount() > 0) {
															echo '<span class="label label-success">Booked</span>';
														}
														?>
													</td>
												</tr>
												<?php
											}
										} else {
											echo '<tr><td colspan="7">No upcoming classes available.</td></tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- Trainers Section end -->

	<!-- Footer Section -->
	<?php include 'include/footer.php'; ?>
	<!-- Footer Section end -->

	<div class="back-to-top"><img src="img/icons/up-arrow.png" alt=""></div>

	<!--====== Javascripts & Jquery ======-->
	<script src="js/vendor/jquery-3.2.1.min.js"></script>
	<script src="js/popper.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery.slicknav.min.js"></script>
	<script src="js/owl.carousel.min.js"></script>
	<script src="js/jquery.nice-select.min.js"></script>
	<script src="js/jquery-ui.min.js"></script>
	<script src="js/jquery.magnific-popup.min.js"></script>
	<script src="js/main.js"></script>

	<!-- FullCalendar JS (from admin directory)-->
	<script src="../admin/js/plugins/fullcalendar.min.js"></script>
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			var calendarEl = document.getElementById('calendar');
			var calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				headerToolbar: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,timeGridDay'
				},
				events: function(fetchInfo, successCallback, failureCallback) {
					// Fetch classes via AJAX
					$.ajax({
						url: 'classes_fetch.php',
						type: 'GET',
						dataType: 'json',
						success: function(response) {
							successCallback(response);
						},
						error: function() {
							failureCallback();
						}
					},
					eventClick: function(info) {
						// Alert for now, or show modal
						alert('Class: ' + info.event.title + '\nInstructor: ' + info.event.extendedProps.instructor + '\nDate: ' + info.event.start.toLocaleString());
					}
				});
				calendar.render();
			});
		</script>
</body>
</html>