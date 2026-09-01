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

	// Load the member's enrollment (if any) and its payment status
	$enrollment = null;
	$payment = null;
	if($series){
		$chk2 = $dbh->prepare("SELECT * FROM tblclass_enrollment WHERE series_id=:series_id AND user_id=:user_id");
		$chk2->bindParam(':series_id',$series_id,PDO::PARAM_INT);
		$chk2->bindParam(':user_id',$user_id,PDO::PARAM_INT);
		$chk2->execute();
		$enrollment = $chk2->fetch(PDO::FETCH_OBJ);

		if($enrollment){
			$psql = $dbh->prepare("SELECT * FROM tblpayments WHERE payment_type='class_series' AND reference_id=:enrollment_id ORDER BY id DESC LIMIT 1");
			$psql->bindParam(':enrollment_id',$enrollment->id,PDO::PARAM_INT);
			$psql->execute();
			$payment = $psql->fetch(PDO::FETCH_OBJ);
		}
	}
	$is_enrolled = ($enrollment !== false && $enrollment !== null);
	$payment_status = $enrollment ? $enrollment->payment_status : 'none';
	// Load profile mobile as a convenience placeholder
	$profile_mobile = "";
	$mob = $dbh->prepare("SELECT mobile FROM tbluser WHERE id=:id");
	$mob->bindParam(':id',$user_id,PDO::PARAM_INT);
	$mob->execute();
	$mrow = $mob->fetch(PDO::FETCH_OBJ);
	if($mrow){ $profile_mobile = $mrow->mobile; }
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
								<!-- Pay & Enroll flow -->
								<div id="enrollBox">
									<form id="payEnrollForm" method="post" style="margin-bottom:20px;">
										<div class="form-group">
											<label class="control-label">Enter M-Pesa Phone Number (e.g. 07XXXXXXXX)</label>
											<input class="form-control" type="text" name="phone" id="phone" placeholder="07XXXXXXXX" value="<?php echo htmlentities($profile_mobile);?>">
										</div>
										<button type="submit" name="pay_enroll" class="btn btn-primary">Pay &amp; Enroll</button>
									</form>
								</div>
								<div id="payResult" style="display:none;"></div>
							<?php } elseif($payment_status == 'paid'){ ?>
								<div class="alert alert-success">
									<strong>Payment Successful — You're Enrolled</strong>
									<p>Amount: Ksh <?php echo number_format((float)$series->price, 2);?>
									<?php if($payment && $payment->transaction_receipt){ ?> | M-Pesa Receipt: <strong><?php echo htmlentities($payment->transaction_receipt);?></strong><?php } ?></p>
								</div>
							<?php } elseif($payment_status == 'pending'){ ?>
								<div class="alert alert-warning">
									<strong>Payment Pending</strong>
									<p>Please check your phone and enter your M-Pesa PIN to complete payment. Your enrollment will be confirmed once payment is received.</p>
									<button type="button" class="btn btn-info" id="checkStatusBtn">Check Payment Status</button>
								</div>
								<div id="payResult" style="display:none;"></div>
							<?php } elseif($payment_status == 'failed'){ ?>
								<div class="alert alert-danger">
									<strong>Payment Failed</strong>
									<p>Your payment was not completed. Please try enrolling again.</p>
								</div>
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
	<script>
	$(document).ready(function(){
		var seriesId = <?php echo $series_id;?>;
		var enrollmentId = <?php echo $enrollment ? $enrollment->id : 0;?>;

		// Pay & Enroll
		$('#payEnrollForm').on('submit', function(e){
			e.preventDefault();
			var phone = $('#phone').val();
			$('#payResult').show().html('<div class="alert alert-info">Initiating payment... please wait.</div>');
			$.post('mpesa/initiate.php', {payment_type: 'class_series', reference_id: seriesId, phone: phone}, function(res){
				if(res.success){
					$('#enrollBox').hide();
					$('#payResult').html(
						'<div class="alert alert-warning">' +
						'<strong>Payment Pending</strong>' +
						'<p>Please check your phone and enter your M-Pesa PIN to complete payment. Your enrollment will be confirmed once payment is received.</p>' +
						'<button type="button" class="btn btn-info" id="checkStatusBtn">Check Payment Status</button>' +
						'</div>'
					);
					enrollmentId = res.reference_id;
					bindCheckStatus();
				} else {
					$('#payResult').html('<div class="alert alert-danger">' + res.message + '</div>');
				}
			}, 'json').fail(function(){
				$('#payResult').html('<div class="alert alert-danger">Could not reach the payment service. Please try again.</div>');
			});
		});

		function bindCheckStatus(){
			$('#checkStatusBtn').on('click', function(){
				$(this).prop('disabled', true).text('Checking...');
				$.get('mpesa/status.php', {payment_type: 'class_series', reference_id: enrollmentId}, function(res){
					if(res.success){
						if(res.status == 'paid'){
							$('#payResult').html(
								'<div class="alert alert-success">' +
								'<strong>Payment Successful — You\'re Enrolled</strong>' +
								'<p>Amount: Ksh ' + Number(res.amount).toLocaleString(undefined, {minimumFractionDigits:2}) +
								(res.receipt ? ' | M-Pesa Receipt: <strong>' + res.receipt + '</strong>' : '') + '</p>' +
								'</div>'
							);
						} else if(res.status == 'failed'){
							$('#payResult').html('<div class="alert alert-danger"><strong>Payment Failed</strong><p>Your payment was not completed. Please try enrolling again.</p></div>');
						} else {
							$('#payResult').html(
								'<div class="alert alert-warning">' +
								'<strong>Payment Pending</strong>' +
								'<p>Payment has not been confirmed yet. Please check your phone and enter your M-Pesa PIN, then check again.</p>' +
								'<button type="button" class="btn btn-info" id="checkStatusBtn">Check Payment Status</button>' +
								'</div>'
							);
							bindCheckStatus();
						}
					} else {
						$('#payResult').html('<div class="alert alert-danger">' + res.message + '</div>');
					}
				}, 'json').always(function(){
					$('#checkStatusBtn').prop('disabled', false).text('Check Payment Status');
				});
			});
		}

		// Bind if already pending on page load
		if(enrollmentId > 0 && $('#checkStatusBtn').length){
			bindCheckStatus();
		}
	});
	</script>
</body>
</html>
