<?php 
session_start();
error_reporting(0);
include 'include/config.php';
$uid=$_SESSION['uid'];
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>Gym Management System</title>
	<meta charset="UTF-8">
	<meta name="description" content="Ahana Yoga HTML Template">
	<meta name="keywords" content="yoga, html">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- Stylesheets -->
	<link rel="stylesheet" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" href="css/font-awesome.min.css"/>
	<link rel="stylesheet" href="css/owl.carousel.min.css"/>
	<link rel="stylesheet" href="css/nice-select.css"/>
	<link rel="stylesheet" href="css/magnific-popup.css"/>
	<link rel="stylesheet" href="css/slicknav.min.css"/>
	<link rel="stylesheet" href="css/animate.css"/>

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
					<h2>Home</h2>
					<p>Physical Activity Or Can Improve Your Health</p>
				</div>
			</div>
		</div>
	</section>

	

	<!-- Pricing Section -->
	<section class="pricing-section spad">
		<div class="container">
			<div class="section-title text-center">
				<img src="img/icons/logo-icon.png" alt="">
				<h2>Pricing plans</h2>
				<p>Practice Yoga to perfect physical beauty, take care of your soul and enjoy life more fully!</p>
			</div>
			<div class="row">
				        <?php 

						$sql ="SELECT id, category, titlename, PackageType, PackageDuratiobn, Price, uploadphoto, Description, create_date from tbladdpackage";
						$query= $dbh -> prepare($sql);
						$query-> execute();
						$results = $query -> fetchAll(PDO::FETCH_OBJ);
						$cnt=1;
						if($query -> rowCount() > 0)
						{
						foreach($results as $result)
						{
						?>
				<div class="col-lg-3 col-sm-6">
					<div class="pricing-item begginer">
						<div class="pi-top">
							<h4><?php echo $result->titlename;?></h4>
						</div>
						<div class="pi-price">
							<h3>Ksh <?php echo number_format((float)$result->Price, 2);?></h3>
							<p>	<?php echo $result->PackageDuratiobn;?></p>
						</div>
						<ul>
							<?php echo $result->Description;?>
							
						</ul>
						<?php if(strlen($_SESSION['uid'])==0): ?>
						<a href="login.php" class="site-btn sb-line-gradient">Subscribe</a>
						<?php else :?>
							<?php
							$sub = $dbh->prepare("SELECT status, payment_status FROM tblsubscriptions WHERE package_id=:pid AND user_id=:uid ORDER BY id DESC LIMIT 1");
							$sub->bindParam(':pid',$result->id,PDO::PARAM_INT);
							$sub->bindParam(':uid',$uid,PDO::PARAM_INT);
							$sub->execute();
							$subrow = $sub->fetch(PDO::FETCH_OBJ);
							$sub_status = $subrow ? $subrow->status : 'none';
							$pay_status = $subrow ? $subrow->payment_status : 'none';
							?>
							<?php if($sub_status == 'active'): ?>
								<span class="site-btn sb-line-gradient" style="cursor:default;">Subscribed</span>
							<?php elseif($sub_status == 'pending'): ?>
								<span class="site-btn sb-line-gradient" style="cursor:default;">Payment Pending</span>
							<?php else: ?>
								<button type="button" class="site-btn sb-line-gradient pay-subscribe-btn"
									data-package-id="<?php echo htmlentities($result->id);?>"
									data-package-name="<?php echo htmlentities($result->titlename);?>"
									data-price="<?php echo htmlentities($result->Price);?>">Pay &amp; Subscribe</button>
							<?php endif;?>
						<?php endif;?>
					</div>
				</div>
				<?php  $cnt=$cnt+1; } } ?>
			</div>
		</div>
	</section>
		

	<!-- Footer Section -->
	<?php include 'include/footer.php'; ?>
	<!-- Footer Section end -->

	<div class="back-to-top"><img src="img/icons/up-arrow.png" alt=""></div>

	<!-- Search model end -->

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

	<!-- M-Pesa Pay & Subscribe Modal -->
	<div class="modal fade" id="payModal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title">Pay &amp; Subscribe</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<p><strong id="payPkgName"></strong></p>
					<p>Amount: <strong>Ksh <span id="payPkgPrice"></span></strong></p>
					<div class="form-group">
						<label class="control-label">Enter M-Pesa Phone Number (e.g. 07XXXXXXXX)</label>
						<input class="form-control" type="text" id="payPhone" placeholder="07XXXXXXXX">
					</div>
					<div id="payResult"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" id="payBtn">Pay Now</button>
				</div>
			</div>
		</div>
	</div>

	<script>
	$(document).ready(function(){
		var currentPackageId = 0;
		var currentRefId = 0;

		$('.pay-subscribe-btn').on('click', function(){
			currentPackageId = $(this).data('package-id');
			$('#payPkgName').text($(this).data('package-name'));
			$('#payPkgPrice').text(Number($(this).data('price')).toLocaleString(undefined, {minimumFractionDigits:2}));
			$('#payPhone').val('');
			$('#payResult').html('');
			$('#payBtn').prop('disabled', false).text('Pay Now');
			$('#payModal').modal('show');
		});

		$('#payBtn').on('click', function(){
			var phone = $('#payPhone').val();
			if(!phone){
				$('#payResult').html('<div class="alert alert-danger">Please enter your M-Pesa phone number.</div>');
				return;
			}
			$(this).prop('disabled', true).text('Initiating...');
			$('#payResult').html('<div class="alert alert-info">Initiating payment... please wait.</div>');
			$.post('mpesa/initiate.php', {payment_type: 'package', reference_id: currentPackageId, phone: phone}, function(res){
				if(res.success){
					currentRefId = res.reference_id;
					$('#payResult').html(
						'<div class="alert alert-warning">' +
						'<strong>Payment Pending</strong>' +
						'<p>Please check your phone and enter your M-Pesa PIN to complete payment. Your subscription will be activated once payment is received.</p>' +
						'<button type="button" class="btn btn-info" id="checkStatusBtn">Check Payment Status</button>' +
						'</div>'
					);
					bindCheckStatus();
				} else {
					$('#payResult').html('<div class="alert alert-danger">' + res.message + '</div>');
					$('#payBtn').prop('disabled', false).text('Pay Now');
				}
			}, 'json').fail(function(){
				$('#payResult').html('<div class="alert alert-danger">Could not reach the payment service. Please try again.</div>');
				$('#payBtn').prop('disabled', false).text('Pay Now');
			});
		});

		function bindCheckStatus(){
			$('#checkStatusBtn').on('click', function(){
				$(this).prop('disabled', true).text('Checking...');
				$.get('mpesa/status.php', {payment_type: 'package', reference_id: currentRefId}, function(res){
					if(res.success){
						if(res.status == 'paid'){
							$('#payResult').html(
								'<div class="alert alert-success">' +
								'<strong>Payment Successful — Subscription Active</strong>' +
								'<p>Amount: Ksh ' + Number(res.amount).toLocaleString(undefined, {minimumFractionDigits:2}) +
								(res.receipt ? ' | M-Pesa Receipt: <strong>' + res.receipt + '</strong>' : '') + '</p>' +
								'</div>'
							);
							$('#payBtn').prop('disabled', true).text('Subscribed');
						} else if(res.status == 'failed'){
							$('#payResult').html('<div class="alert alert-danger"><strong>Payment Failed</strong><p>Your payment was not completed. Please try subscribing again.</p></div>');
							$('#payBtn').prop('disabled', false).text('Pay Now');
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
	});
	</script>
	
	</body>
</html>
