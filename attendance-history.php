<?php
session_start();
error_reporting(0);
require_once('include/config.php');
if(strlen($_SESSION["uid"])==0)
{
    header('location:login.php');
}
else{
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>Gym Management System | Attendance History</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Stylesheets -->
	<link rel="stylesheet" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" href="css/font-awesome.min.css"/>
	<link rel="stylesheet" href="css/owl.carousel.min.css"/>
	<link rel="stylesheet" href="css/nice-select.css"/>
	<link rel="stylesheet" href="css/slicknav.min.css">

	<!-- Main Stylesheets -->
	<link rel="stylesheet" href="css/style.css"/>

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
					<h2>My Attendance History</h2>
				</div>
			</div>
		</div>
	</section>
	<!-- Page top Section end -->

	<!-- Attendance History Section -->
	<section class="contact-page-section spad overflow-hidden">
		<div class="container">

			<div class="row">
				<div class="col-lg-2">
				</div>
				<div class="col-lg-8">

					<?php
					// Get user ID from session
					$uid = $_SESSION['uid'];

					// Fetch attendance history for this user
					$sql = "SELECT check_in, check_out,
	                            TIMESTAMPDIFF(HOUR, check_in,
	                                CASE WHEN check_out IS NULL THEN NOW() ELSE check_out END) as hours_spent
	                        FROM tblattendance
	                        WHERE user_id = :uid
	                        ORDER BY check_in DESC";
					$query = $dbh->prepare($sql);
					$query->bindParam(':uid',$uid,PDO::PARAM_INT);
					$query->execute();
					$results = $query->fetchAll(PDO::FETCH_OBJ);

					if($query->rowCount() > 0)
					{
					?>
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>#</th>
								<th>Date</th>
								<th>Check-In Time</th>
								<th>Check-Out Time</th>
								<th>Hours Spent</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$cnt=1;
							foreach($results as $result)
							{
							?>
							<tr>
								<td><?php echo $cnt++; ?></td>
								<td><?php echo date('M j, Y', strtotime($result->check_in)); ?></td>
								<td><?php echo date('g:i A', strtotime($result->check_in)); ?></td>
								<td>
									<?php
									if($result->check_out)
									{
										echo date('g:i A', strtotime($result->check_out));
									}
									else
									{
										echo "<span class='badge badge-warning'>Still Checked In</span>";
									}
									?>
								</td>
								<td><?php echo number_format($result->hours_spent, 2); ?> hrs</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<?php
					}
					else
					{
					?>
					<div class="alert alert-info">
						You have no attendance records yet. Check in at the gym to start tracking your attendance.
					</div>
					<?php
					}
					?>

				</div>
				<div class="col-lg-2">
				</div>
			</div>
		</div>
	</section>
	<!-- Attendance History Section end -->

<?php include 'include/footer.php'; ?>
	<!-- Footer Section end -->

	<div class="back-to-top"><img src="img/icons/up-arrow.png" alt=""></div>

		<!-- Search model -->

		<!-- Search model end -->

		<!--====== Javascripts & Jquery ======-->
		<script src="js/vendor/jquery-3.2.1.min.js"></script>
		<script src="js/popper.min.js"></script>
		<script src="js/popper.min.js"></script>
		<script src="js/bootstrap.min.js"></script>
		<script src="js/jquery.slicknav.min.js"></script>
		<script src="js/owl.carousel.min.js"></script>
		<script src="js/jquery.nice-select.min.js"></script>
		<script src="js/jquery-ui.min.js"></script>
		<script src="js/jquery.magnific-popup.min.js"></script>
		<script src="js/main.js"></script>

		</body>
</html>
<?php } ?>