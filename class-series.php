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
	<title>User | Class Series</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Stylesheets -->
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
					<h2>Class Series</h2>
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
							<h3>Available Class Series</h3>
							<div class="table-responsive">
								<table class="table table-hover table-bordered" id="sampleTable">
									<thead>
										<tr>
											<th>Title</th>
											<th>Trainer</th>
											<th>Total Sessions</th>
											<th>Frequency</th>
											<th>Start Date</th>
											<th>Enrolled / Capacity</th>
											<th>Price</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$sql = "SELECT cs.*, t.name AS trainer_name FROM tblclass_series cs JOIN tbltrainers t ON t.id = cs.trainer_id WHERE cs.status = 1 ORDER BY cs.id DESC";
										$query = $dbh->prepare($sql);
										$query->execute();
										$results = $query->fetchAll(PDO::FETCH_OBJ);
										if($query->rowCount() > 0) {
											foreach($results as $result) {
												// On-demand enrollment count
												$cnt = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_enrollment WHERE series_id=:series_id");
												$cnt->bindParam(':series_id',$result->id,PDO::PARAM_INT);
												$cnt->execute();
												$enrolled = $cnt->fetch(PDO::FETCH_OBJ)->c;
												?>
												<tr>
													<td><?php echo htmlentities($result->title);?></td>
													<td><?php echo htmlentities($result->trainer_name);?></td>
													<td><?php echo htmlentities($result->total_sessions);?></td>
													<td><?php echo htmlentities(ucfirst($result->frequency));?></td>
													<td><?php echo htmlentities($result->start_date);?></td>
													<td><?php echo $enrolled;?> / <?php echo htmlentities($result->capacity);?></td>
													<td>Ksh <?php echo number_format($result->price, 2);?></td>
													<td>
														<a href="class-series-details.php?series=<?php echo $result->id;?>" class="btn btn-sm btn-primary">View &amp; Enroll</a>
													</td>
												</tr>
												<?php
											}
										} else {
											echo '<tr><td colspan="8">No class series available.</td></tr>';
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
