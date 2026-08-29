<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['uid'])==0) {
	  header('location:login.php');
	  }
	$user_id = intval($_SESSION['uid']);
	$series_id = isset($_GET['series']) ? intval($_GET['series']) : 0;

	$series = null;
	if($series_id > 0){
		$sql = "SELECT cs.*, t.name AS trainer_name FROM tblclass_series cs JOIN tbltrainers t ON t.id = cs.trainer_id WHERE cs.id=:id AND cs.status=1";
		$query = $dbh->prepare($sql);
		$query->bindParam(':id',$series_id,PDO::PARAM_INT);
		$query->execute();
		$series = $query->fetch(PDO::FETCH_OBJ);
	}

	$msg = "";
	$errormsg = "";
	if(isset($_POST['enroll']) && $series){
		// Check not already enrolled
		$chk = $dbh->prepare("SELECT id FROM tblclass_enrollment WHERE series_id=:series_id AND user_id=:user_id");
		$chk->bindParam(':series_id',$series_id,PDO::PARAM_INT);
		$chk->bindParam(':user_id',$user_id,PDO::PARAM_INT);
		$chk->execute();
		if($chk->rowCount() > 0){
			$errormsg = "You have already enrolled in this class series.";
		} else {
			// Check capacity (on-demand count)
			$cnt = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_enrollment WHERE series_id=:series_id");
			$cnt->bindParam(':series_id',$series_id,PDO::PARAM_INT);
			$cnt->execute();
			$enrolled = $cnt->fetch(PDO::FETCH_OBJ)->c;
			if($enrolled >= $series->capacity){
				$errormsg = "This class series is full.";
			} else {
				$ins = $dbh->prepare("INSERT INTO tblclass_enrollment (series_id, user_id) VALUES (:series_id, :user_id)");
				$ins->bindParam(':series_id',$series_id,PDO::PARAM_INT);
				$ins->bindParam(':user_id',$user_id,PDO::PARAM_INT);
				$ins->execute();
				if($dbh->lastInsertId() > 0){
					$msg = "You have successfully enrolled in this class series.";
				} else {
					$errormsg = "Enrollment failed. Please try again.";
				}
			}
		}
	}

	// Re-check enrollment status after any action
	$is_enrolled = false;
	if($series){
		$chk2 = $dbh->prepare("SELECT id FROM tblclass_enrollment WHERE series_id=:series_id AND user_id=:user_id");
		$chk2->bindParam(':series_id',$series_id,PDO::PARAM_INT);
		$chk2->bindParam(':user_id',$user_id,PDO::PARAM_INT);
		$chk2->execute();
		$is_enrolled = ($chk2->rowCount() > 0);
	}
	?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>User | Class Series Details</title>
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
					<h2>Class Series Details</h2>
				</div>
			</div>
		</div>
	</section>

	<section class="contact-page-section spad overflow-hidden">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<?php if(!$series){ ?>
						<div class="tile"><div class="tile-body"><div class="alert alert-danger">Class series not found or inactive.</div></div></div>
					<?php } else { ?>
					<div class="tile">
						<div class="tile-body">
							<?php if($msg){ ?><div class="alert alert-success"><?php echo htmlentities($msg);?></div><?php } ?>
							<?php if($errormsg){ ?><div class="alert alert-danger"><?php echo htmlentities($errormsg);?></div><?php } ?>

							<h3><?php echo htmlentities($series->title);?></h3>
							<p><strong>Trainer:</strong> <?php echo htmlentities($series->trainer_name);?></p>
							<p><strong>Description:</strong> <?php echo nl2br(htmlentities($series->description));?></p>
							<p><strong>Total Sessions:</strong> <?php echo htmlentities($series->total_sessions);?> &nbsp;|&nbsp; <strong>Frequency:</strong> <?php echo htmlentities(ucfirst($series->frequency));?></p>
							<p><strong>Start Date:</strong> <?php echo htmlentities($series->start_date);?> &nbsp;|&nbsp; <strong>Price:</strong> Ksh <?php echo number_format($series->price, 2);?></p>

							<?php
							$cnt = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_enrollment WHERE series_id=:series_id");
							$cnt->bindParam(':series_id',$series_id,PDO::PARAM_INT);
							$cnt->execute();
							$enrolled = $cnt->fetch(PDO::FETCH_OBJ)->c;
							?>
							<p><strong>Enrolled:</strong> <?php echo $enrolled;?> / <?php echo htmlentities($series->capacity);?></p>

							<?php if(!$is_enrolled){ ?>
								<form method="post" style="margin-bottom:20px;">
									<button type="submit" name="enroll" class="btn btn-primary">Enroll in this Series</button>
								</form>
							<?php } else { ?>
								<span class="label label-success" style="font-size:14px;">You are enrolled in this series</span>
							<?php } ?>

							<hr />
							<h4>Session Schedule</h4>
							<div class="table-responsive">
								<table class="table table-hover table-bordered">
									<thead>
										<tr>
											<th>Session #</th>
											<th>Date &amp; Time</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$sql = "SELECT * FROM tblclass_sessions WHERE series_id=:series_id ORDER BY session_number";
										$query = $dbh->prepare($sql);
										$query->bindParam(':series_id',$series_id,PDO::PARAM_INT);
										$query->execute();
										$sessions = $query->fetchAll(PDO::FETCH_OBJ);
										if($query->rowCount() > 0){
											foreach($sessions as $s){ ?>
												<tr>
													<td>Session <?php echo htmlentities($s->session_number);?></td>
													<td><?php echo date('d M Y, H:i', strtotime($s->session_date));?></td>
												</tr>
											<?php }
										} else {
											echo '<tr><td colspan="2">No sessions scheduled.</td></tr>';
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<?php } ?>
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
</body>
</html>
