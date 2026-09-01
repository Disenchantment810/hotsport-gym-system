<?php session_start();
error_reporting(0);
include  'include/config.php'; 
if (strlen($_SESSION['adminid']==0)) {
  header('location:logout.php');
  } else{
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
if (!in_array($filter_type, array('all','package','class_series'))) { $filter_type = 'all'; }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Admin | Payment Report</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
  </head>
  <body class="app sidebar-mini rtl">
    <!-- Navbar-->
   <?php include 'include/header.php'; ?>
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <?php include 'include/sidebar.php'; ?>
      <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM MANAGEMENT SYSTEM | Manage Members, Packages, Bookings and Payments</marquee>

    <main class="app-content">
      <div class="row">
        <div class="col-md-12">
          <div class="tile">
            <h3 class="tile-title">Payment Report</h3>
            <div class="tile-body">
              <form class="row" method="get">
                <div class="form-group col-md-3">
                  <label class="control-label">Payment Type</label>
                  <select class="form-control" name="type">
                    <option value="all" <?php if($filter_type=='all') echo 'selected';?>>All</option>
                    <option value="package" <?php if($filter_type=='package') echo 'selected';?>>Packages</option>
                    <option value="class_series" <?php if($filter_type=='class_series') echo 'selected';?>>Trainer Sessions</option>
                  </select>
                </div>
                <div class="form-group col-md-3">
                  <label class="control-label">From Date</label>
                  <input class="form-control" type="date" name="fdate" id="fdate" value="<?php echo isset($_GET['fdate']) ? htmlentities($_GET['fdate']) : '';?>">
                </div>
                <div class="form-group col-md-3">
                  <label class="control-label">To Date</label>
                  <input class="form-control" type="date" name="todate" id="todate" value="<?php echo isset($_GET['todate']) ? htmlentities($_GET['todate']) : '';?>">
                </div>
                <div class="form-group col-md-3 align-self-end">
                  <input type="Submit" name="Submit" id="Submit" class="btn btn-primary" value="Filter">
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="tile">
            <div class="tile-body">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Sr.No</th>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Amount (Ksh)</th>
                    <th>M-Pesa Receipt</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $where = "WHERE p.status='SUCCESS'";
                  $params = array();
                  if ($filter_type != 'all') {
                      $where .= " AND p.payment_type = :ptype";
                      $params[':ptype'] = $filter_type;
                  }
                  if (isset($_GET['fdate']) && $_GET['fdate'] != '' && isset($_GET['todate']) && $_GET['todate'] != '') {
                      $where .= " AND date(p.created_at) BETWEEN :fdate AND :tdate";
                      $params[':fdate'] = $_GET['fdate'];
                      $params[':tdate'] = $_GET['todate'];
                  }
                  $sql = "SELECT p.*, u.fname, u.lname, u.email,
                          CASE p.payment_type
                            WHEN 'package' THEN (SELECT titlename FROM tbladdpackage WHERE id = p.reference_id)
                            WHEN 'class_series' THEN (SELECT title FROM tblclass_series WHERE id = p.reference_id)
                          END AS item_name
                          FROM tblpayments p
                          JOIN tbluser u ON u.id = p.user_id
                          $where
                          ORDER BY p.id DESC";
                  $query = $dbh->prepare($sql);
                  foreach ($params as $k => $v) { $query->bindValue($k, $v, PDO::PARAM_STR); }
                  $query->execute();
                  $results = $query->fetchAll(PDO::FETCH_OBJ);
                  $cnt = 1;
                  $total = 0;
                  if ($query->rowCount() > 0) {
                      foreach ($results as $result) {
                          $total += (float)$result->amount;
                          $typeLabel = ($result->payment_type == 'package') ? 'Package' : 'Trainer Session';
                          $statusLabel = ucfirst(strtolower($result->status));
                          $badge = ($result->status == 'SUCCESS') ? 'success' : (($result->status == 'PENDING') ? 'warning' : 'danger');
                  ?>
                  <tr>
                    <td><?php echo $cnt;?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($result->created_at));?></td>
                    <td><?php echo htmlentities($result->fname . ' ' . $result->lname);?><br><small><?php echo htmlentities($result->email);?></small></td>
                    <td><?php echo htmlentities($result->item_name);?></td>
                    <td><?php echo $typeLabel;?></td>
                    <td><?php echo number_format((float)$result->amount, 2);?></td>
                    <td><?php echo $result->transaction_receipt ? htmlentities($result->transaction_receipt) : '-';?></td>
                    <td><span class="badge badge-<?php echo $badge;?>"><?php echo $statusLabel;?></span></td>
                  </tr>
                  <?php $cnt++; } } else { ?>
                  <tr><td colspan="8" class="text-center">No payments found.</td></tr>
                  <?php } ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="5" class="text-right">Total (Ksh)</th>
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
     <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/plugins/pace.min.js"></script>
    <script type="text/javascript" src="js/plugins/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="js/plugins/dataTables.bootstrap.min.js"></script>
    <script type="text/javascript">$('#sampleTable').DataTable();</script>
  </body>	
</html>
<?php } ?>