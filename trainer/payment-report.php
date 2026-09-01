<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Payment Report</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="../admin/css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  </head>
  <body class="app sidebar-mini rtl">
    <!-- Navbar-->
   <?php include 'include/header.php'; ?>
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <?php include 'include/sidebar.php'; ?>
    <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM SYSTEM | Trainer Portal</marquee>

    <main class="app-content">
     <h3>Payment Report (My Sessions)</h3>
     <hr />

      <div class="row">
        <div class="col-md-12">
          <div class="tile">
            <div class="tile-body">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Session</th>
                    <th>Amount (Ksh)</th>
                    <th>M-Pesa Receipt</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                  $sql="SELECT p.*, u.fname, u.lname, u.email, cs.title AS series_title
                        FROM tblpayments p
                        JOIN tblclass_enrollment e ON e.id = p.reference_id
                        JOIN tblclass_series cs ON cs.id = e.series_id
                        JOIN tbluser u ON u.id = p.user_id
                        WHERE p.payment_type = 'class_series' AND cs.trainer_id = :trainerid
                        ORDER BY p.id DESC";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  $total = 0;
                  if($query -> rowCount() > 0)
                  {
                  foreach($results as $result)
                  {
                    $total += (float)$result->amount;
                    $statusLabel = ucfirst(strtolower($result->status));
                    $badge = ($result->status == 'SUCCESS') ? 'success' : (($result->status == 'PENDING') ? 'warning' : 'danger');
                    ?>
	                  <tr>
	                    <td><?php echo date('d M Y, H:i', strtotime($result->created_at));?></td>
	                    <td><?php echo htmlentities($result->fname.' '.$result->lname);?><br><small><?php echo htmlentities($result->email);?></small></td>
	                    <td><?php echo htmlentities($result->series_title);?></td>
	                    <td><?php echo number_format((float)$result->amount, 2);?></td>
	                    <td><?php echo $result->transaction_receipt ? htmlentities($result->transaction_receipt) : '-';?></td>
	                    <td><span class="badge badge-<?php echo $badge;?>"><?php echo $statusLabel;?></span></td>
	                  </tr>
	                    <?php } } else {
	                      echo '<tr><td colspan="6">No payments found for your sessions.</td></tr>';
	                    } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="3" class="text-right">Total (Ksh)</th>
                    <th><?php echo number_format($total, 2);?></th>
                    <th colspan="2"></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
    <?php include_once 'include/footer.php' ?>
    <!-- Essential javascripts for application to work-->
     <script src="../admin/js/jquery-3.2.1.min.js"></script>
    <script src="../admin/js/popper.min.js"></script>
    <script src="../admin/js/bootstrap.min.js"></script>
    <script src="../admin/js/main.js"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="../admin/js/plugins/pace.min.js"></script>
    <!-- Page specific javascripts-->
    <!-- Data table plugin-->
    <script type="text/javascript" src="../admin/js/plugins/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../admin/js/plugins/dataTables.bootstrap.min.js"></script>
    <script type="text/javascript">$('#sampleTable').DataTable();</script>
  </body>
</html>