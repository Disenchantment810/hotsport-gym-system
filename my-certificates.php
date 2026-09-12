<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['uid'])==0) {
	  header('location:login.php');
	  exit;
	}
	$user_id = intval($_SESSION['uid']);
	?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>User | My Certificates</title>
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
					<h2>My Certificates</h2>
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
							<h3>Certificates I Have Earned</h3>
							<div class="table-responsive">
								<table class="table table-hover table-bordered" id="sampleTable">
									<thead>
										<tr>
											<th>Series</th>
											<th>Trainer</th>
											<th>Completion Date</th>
											<th>Certificate Code</th>
											<th>Status</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$sql = "SELECT c.id, c.certificate_code, c.completion_date, c.is_active, c.issued_at,
												cs.id AS series_id, cs.title AS series_title, cs.total_sessions,
												t.name AS trainer_name
												FROM tblcertificates c
												JOIN tblclass_series cs ON cs.id = c.series_id
												JOIN tbltrainers t ON t.id = c.trainer_id
												WHERE c.user_id=:user_id
												ORDER BY c.completion_date DESC, c.id DESC";
										$query = $dbh->prepare($sql);
										$query->bindParam(':user_id',$user_id,PDO::PARAM_INT);
										$query->execute();
										$results = $query->fetchAll(PDO::FETCH_OBJ);
										if($query->rowCount() > 0) {
											foreach($results as $result) {
												?>
												<tr>
													<td><a href="class-series-details.php?series=<?php echo $result->series_id;?>"><?php echo htmlentities($result->series_title);?></a></td>
													<td><?php echo htmlentities($result->trainer_name);?></td>
													<td><?php echo date('d M Y', strtotime($result->completion_date));?></td>
													<td><?php echo htmlentities($result->certificate_code);?></td>
													<td><?php echo ($result->is_active==1) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Revoked</span>';?></td>
												</tr>
												<?php
											}
										} else {
											echo '<tr><td colspan="5">You have not received any certificates yet. Certificates are awarded after completing 80% of a class series. <a href="class-series.php">Browse class series</a></td></tr>';
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
