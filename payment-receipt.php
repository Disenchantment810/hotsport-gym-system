<?php session_start();
error_reporting(0);
require_once('include/config.php');
if(strlen( $_SESSION["uid"])==0)
    {   
header('location:login.php');
}
else{
$uid=$_SESSION['uid'];
$pid = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql="SELECT p.*, u.fname, u.lname, u.email, u.mobile,
      CASE p.payment_type
        WHEN 'package' THEN (SELECT titlename FROM tbladdpackage WHERE id = p.reference_id)
        WHEN 'class_series' THEN (SELECT title FROM tblclass_series WHERE id = (SELECT series_id FROM tblclass_enrollment WHERE id = p.reference_id))
      END AS item_name
      FROM tblpayments p
      JOIN tbluser u ON u.id = p.user_id
      WHERE p.id = :pid AND p.user_id = :uid";
$query= $dbh->prepare($sql);
$query->bindParam(':pid',$pid, PDO::PARAM_INT);
$query->bindParam(':uid',$uid, PDO::PARAM_STR);
$query-> execute();
$pay = $query->fetch(PDO::FETCH_OBJ);
if(!$pay){
    header('location:payment-history.php');
    exit;
}
$typeLabel = ($pay->payment_type == 'package') ? 'Package Subscription' : 'Trainer Session Enrollment';
$statusLabel = ucfirst(strtolower($pay->status));
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
	<title>Payment Receipt</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/bootstrap.min.css"/>
	<link rel="stylesheet" href="css/font-awesome.min.css"/>
	<link rel="stylesheet" href="css/style.css"/>
	<style>
		.receipt-box { max-width: 700px; margin: 40px auto; border: 1px solid #ddd; padding: 30px; }
		.receipt-header { text-align: center; border-bottom: 2px solid #e92f33; padding-bottom: 15px; margin-bottom: 20px; }
		.receipt-header h2 { margin: 0; color: #e92f33; }
		.receipt-table td { padding: 8px 5px; }
		.receipt-table td.label { font-weight: bold; width: 40%; }
		@media print { .no-print { display: none; } }
	</style>
</head>
<body>
	<div class="container">
		<div class="receipt-box">
			<div class="receipt-header">
				<h2>HOTSPORT GYM</h2>
				<p>Payment Receipt</p>
			</div>
			<table class="table receipt-table">
				<tr><td class="label">Receipt No.</td><td>#<?php echo str_pad($pay->id, 6, '0', STR_PAD_LEFT);?></td></tr>
				<tr><td class="label">Member</td><td><?php echo htmlentities($pay->fname . ' ' . $pay->lname);?></td></tr>
				<tr><td class="label">Email</td><td><?php echo htmlentities($pay->email);?></td></tr>
				<tr><td class="label">Phone</td><td><?php echo htmlentities($pay->phone_number);?></td></tr>
				<tr><td class="label">Item</td><td><?php echo htmlentities($pay->item_name);?></td></tr>
				<tr><td class="label">Type</td><td><?php echo $typeLabel;?></td></tr>
				<tr><td class="label">Amount</td><td>Ksh <?php echo number_format((float)$pay->amount, 2);?></td></tr>
				<tr><td class="label">Payment Date</td><td><?php echo date('d M Y, H:i', strtotime($pay->created_at));?></td></tr>
				<tr><td class="label">M-Pesa Receipt</td><td><?php echo $pay->transaction_receipt ? htmlentities($pay->transaction_receipt) : '-';?></td></tr>
				<tr><td class="label">Transaction Date</td><td><?php echo $pay->transaction_date ? date('d M Y, H:i', strtotime($pay->transaction_date)) : '-';?></td></tr>
				<tr><td class="label">Status</td><td><?php echo $statusLabel;?></td></tr>
			</table>
			<div class="text-center no-print" style="margin-top:20px;">
				<button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
				<a href="payment-history.php" class="btn btn-default">Back to Payment History</a>
			</div>
		</div>
	</div>
</body>
</html>
<?php } ?>