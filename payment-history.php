<?php session_start();
error_reporting(0);
require_once('include/config.php');
if(strlen( $_SESSION["uid"])==0)
    {   
header('location:login.php');
}
else{
$uid=$_SESSION['uid'];
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>User | Payment History</title>
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
					<h2>Payment History</h2>
					
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
					<table class="table table-bordered">
    <thead>
      <tr>
        <th>Sr.No</th>
        <th>Date</th>
        <th>Item</th>
        <th>Type</th>
        <th>Amount</th>
        <th>M-Pesa Receipt</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
          <?php
          $sql="SELECT p.*,
                CASE p.payment_type
                  WHEN 'package' THEN (SELECT titlename FROM tbladdpackage WHERE id = p.reference_id)
                  WHEN 'class_series' THEN (SELECT title FROM tblclass_series WHERE id = (SELECT series_id FROM tblclass_enrollment WHERE id = p.reference_id))
                END AS item_name
                FROM tblpayments p
                WHERE p.user_id = :uid
                ORDER BY p.id DESC";
          $query= $dbh->prepare($sql);
          $query->bindParam(':uid',$uid, PDO::PARAM_STR);
          $query-> execute();
          $results = $query -> fetchAll(PDO::FETCH_OBJ);
          $cnt=1;
          if($query -> rowCount() > 0)
          {
          foreach($results as $result)
          {
            $typeLabel = ($result->payment_type == 'package') ? 'Package' : 'Trainer Session';
            $statusLabel = ucfirst(strtolower($result->status));
            $badge = ($result->status == 'SUCCESS') ? 'success' : (($result->status == 'PENDING') ? 'warning' : 'danger');
          ?>
	
                <tbody>
                  <tr>
                    <td><?php echo($cnt);?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($result->created_at));?></td>
                    <td><?php echo htmlentities($result->item_name);?></td>
                    <td><?php echo $typeLabel;?></td>
                    <td>Ksh <?php echo number_format((float)$result->amount, 2);?></td>
                    <td><?php echo $result->transaction_receipt ? htmlentities($result->transaction_receipt) : '-';?></td>
                    <td><span class="badge badge-<?php echo $badge;?>"><?php echo $statusLabel;?></span></td>
                    <td><a href="payment-receipt.php?id=<?php echo htmlentities($result->id);?>"><button class="btn btn-primary" type="button">Receipt</button></a></td>
                  </tr>
                    <?php  $cnt=$cnt+1; } } else { ?>
                    <tr><td colspan="8" class="text-center">No payments found.</td></tr>
                    <?php } ?>
              
                </tbody>
  </table>
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
 <style>
.errorWrap {
    padding: 10px;
    margin: 0 0 20px 0;
    background: #dd3d36;
    color:#fff;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
.succWrap{
    padding: 10px;
    margin: 0 0 20px 0;
    background: #5cb85c;
    color:#fff;
    -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
    box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
}
        </style>
        <?php } ?>