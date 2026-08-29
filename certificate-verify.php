<?php session_start();
	error_reporting(0);
	include  'include/config.php';

	$status = "";
	$result = null;
	if(isset($_POST['verify'])){
		$code = trim($_POST['certificate_code']);
		if($code != ""){
			$sql = "SELECT c.certificate_code, c.completion_date, c.is_active, cs.title AS series_title, u.fname, u.lname, t.name AS trainer_name
					FROM tblcertificates c
					JOIN tblclass_series cs ON cs.id = c.series_id
					JOIN tbluser u ON u.id = c.user_id
					JOIN tbltrainers t ON t.id = c.trainer_id
					WHERE c.certificate_code = :code";
			$query = $dbh->prepare($sql);
			$query->bindParam(':code',$code,PDO::PARAM_STR);
			$query->execute();
			$result = $query->fetch(PDO::FETCH_OBJ);
			if($result){
				$status = ($result->is_active == 1) ? 'valid' : 'revoked';
			} else {
				$status = 'notfound';
			}
		}
	}
	?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>Certificate Verification</title>
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
					<h2>Certificate Verification</h2>
				</div>
			</div>
		</div>
	</section>

	<section class="contact-page-section spad overflow-hidden">
		<div class="container">
			<div class="row">
				<div class="col-lg-8 m-auto">
					<div class="tile">
						<div class="tile-body">
							<h3>Verify a Certificate</h3>
							<p>Enter the certificate code to verify its authenticity.</p>
							<form method="post">
								<div class="form-group">
									<input class="form-control" type="text" name="certificate_code" placeholder="Enter Certificate Code (e.g. HSP-XXXXXX)" required>
								</div>
								<button type="submit" name="verify" class="btn btn-primary">Verify</button>
							</form>

							<hr />

							<?php if($status == 'valid'){ ?>
								<div class="alert alert-success">
									<strong>VALID CERTIFICATE</strong>
									<p>This certificate is genuine and active.</p>
								</div>
								<table class="table table-bordered">
									<tr><th>Certificate Code</th><td><?php echo htmlentities($result->certificate_code);?></td></tr>
									<tr><th>Member</th><td><?php echo htmlentities($result->fname.' '.$result->lname);?></td></tr>
									<tr><th>Class Series</th><td><?php echo htmlentities($result->series_title);?></td></tr>
									<tr><th>Trainer</th><td><?php echo htmlentities($result->trainer_name);?></td></tr>
									<tr><th>Completion Date</th><td><?php echo htmlentities($result->completion_date);?></td></tr>
								</table>
							<?php } elseif($status == 'revoked'){ ?>
								<div class="alert alert-danger">
									<strong>REVOKED CERTIFICATE</strong>
									<p>This certificate has been revoked and is no longer valid.</p>
								</div>
							<?php } elseif($status == 'notfound'){ ?>
								<div class="alert alert-danger">
									<strong>NOT FOUND</strong>
									<p>No certificate matches the code you entered.</p>
								</div>
							<?php } ?>
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
</body>
</html>
