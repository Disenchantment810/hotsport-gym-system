<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['uid'])==0) {
	  header('location:login.php');
	  }
	$user_id = intval($_SESSION['uid']);
	?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>User | My Enrollments</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" href="css/font-awesome.min.css"/>
	<link rel="stylesheet" href="css/owl.carousel.min.css"/>
	<link rel="stylesheet" href="css/nice-select.css"/>
	<link rel="stylesheet" href="css/slicknav.min.css"/>
	<link rel="stylesheet" href="css/style.css"/>
	<link rel="stylesheet" type="text/css" href="../admin/css/main.css">
</head>
<body>
	<?php include 'include/header.php';?>

	<section class="page-top-section set-bg" data-setbg="img/page-top-bg.jpg">
		<div class="container">
			<div class="row">
				<div class="col-lg-7 m-auto text-white">
					<h2>My Enrollments</h2>
				</div>
			</div>
		</div>
	</section>

	<section class="contact-page-section spad overflow-hidden">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="tile">
						<div class="tile-body">
							<h3>My Class Series Enrollments</h3>
							<div class="table-responsive">
								<table class="table table-hover table-bordered" id="sampleTable">
									<thead>
										<tr>
											<th>Series</th>
											<th>Trainer</th>
											<th>Enrolled Date</th>
											<th>Status</th>
											<th>Payment</th>
											<th>Attendance</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$sql = "SELECT e.id AS enrollment_id, e.enrolled_date, e.status AS enroll_status, e.payment_status, cs.id AS series_id, cs.title, cs.total_sessions, t.name AS trainer_name
												FROM tblclass_enrollment e
												JOIN tblclass_series cs ON cs.id = e.series_id
												JOIN tbltrainers t ON t.id = cs.trainer_id
												WHERE e.user_id=:user_id ORDER BY e.enrolled_date DESC";
										$query = $dbh->prepare($sql);
										$query->bindParam(':user_id',$user_id,PDO::PARAM_INT);
										$query->execute();
										$results = $query->fetchAll(PDO::FETCH_OBJ);
										if($query->rowCount() > 0) {
											foreach($results as $result) {
												// On-demand attendance count for this member in this series
												$att = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_attendance a
													JOIN tblclass_sessions s ON s.id = a.session_id
													WHERE s.series_id=:series_id AND a.enrollment_id=:enrollment_id AND a.status IN ('attended','late')");
												$att->bindParam(':series_id',$result->series_id,PDO::PARAM_INT);
												$att->bindParam(':enrollment_id',$result->enrollment_id,PDO::PARAM_INT);
												$att->execute();
												$attended = $att->fetch(PDO::FETCH_OBJ)->c;
												?>
												<tr>
													<td><a href="class-series-details.php?series=<?php echo $result->series_id;?>"><?php echo htmlentities($result->title);?></a></td>
													<td><?php echo htmlentities($result->trainer_name);?></td>
													<td><?php echo date('d M Y', strtotime($result->enrolled_date));?></td>
													<td><?php echo htmlentities(ucfirst($result->enroll_status));?></td>
													<td>
														<?php
														$pst = $result->payment_status;
														if($pst == 'paid'){ echo '<span class="label label-success">Paid</span>'; }
														elseif($pst == 'pending'){ echo '<span class="label label-warning">Pending</span>'; }
														elseif($pst == 'failed'){ echo '<span class="label label-danger">Failed</span>'; }
														else { echo '<span class="label label-default">'.htmlentities(ucfirst($pst)).'</span>'; }
														?>
													</td>
													<td><?php echo $attended;?> / <?php echo htmlentities($result->total_sessions);?> sessions attended</td>
												</tr>
												<?php
											}
										} else {
											echo '<tr><td colspan="6">You have not enrolled in any class series yet. <a href="class-series.php">Browse class series</a></td></tr>';
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

	<?php include 'include/footer.php'; ?>

	<div class="back-to-top"><img src="img/icons/up-arrow.png" alt=""></div>

	<script src="js/vendor/jquery-3.2.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery.slicknav.min.js"></script>
	<script src="js/owl.carousel.min.js"></script>
	<script src="js/jquery.nice-select.min.js"></script>
	<script src="js/jquery-ui.min.js"></script>
	<script src="js/jquery.magnific-popup.min.js"></script>
	<script src="js/main.js"></script>
	<script type="text/javascript" src="../admin/js/plugins/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="../admin/js/plugins/dataTables.bootstrap.min.js"></script>
	<script type="text/javascript">$('#sampleTable').DataTable();</script>
</body>
</html>
