<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	$series_id = isset($_GET['series']) ? intval($_GET['series']) : 0;

	$series = null;
	if($series_id > 0){
		$sql="SELECT * FROM tblclass_series WHERE id=:id AND trainer_id=:trainerid";
		$query= $dbh->prepare($sql);
		$query->bindParam(':id',$series_id,PDO::PARAM_INT);
		$query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
		$query-> execute();
		$series = $query->fetch(PDO::FETCH_OBJ);
		if(!$series){ $series_id = 0; }
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Attendance Report</title>
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
     <h3>Attendance Report</h3>
     <hr />

     <?php if($series_id == 0){ ?>
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
            <h3 class="tile-title">Select a Class Series</h3>
            <div class="tile-body">
              <form class="row" method="get" action="attendance-report.php">
                <div class="form-group col-md-12">
                  <label class="control-label">Class Series</label>
                  <select class="form-control" name="series" required>
                    <option value="">-- Select Series --</option>
                    <?php
                      $sql="SELECT id,title FROM tblclass_series WHERE trainer_id=:trainerid ORDER BY id DESC";
                      $query= $dbh->prepare($sql);
                      $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
                      $query-> execute();
                      $results = $query -> fetchAll(PDO::FETCH_OBJ);
                      foreach($results as $result){ ?>
                        <option value="<?php echo $result->id;?>"><?php echo htmlentities($result->title);?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group col-md-4 align-self-end">
                  <input type="submit" class="btn btn-primary" value="View Report">
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
     <?php } else { ?>
      <div class="row">
        <div class="col-md-12">
          <div class="tile">
            <h3 class="tile-title">Attendance Report: <?php echo htmlentities($series->title);?></h3>
            <div class="tile-body">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Payment</th>
                    <th>Attended</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Total</th>
                    <th>%</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                  $sql="SELECT e.id AS enrollment_id, e.payment_status, u.fname, u.lname, u.email
                        FROM tblclass_enrollment e
                        JOIN tbluser u ON u.id = e.user_id
                        WHERE e.series_id = :series_id ORDER BY u.fname";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  if($query -> rowCount() > 0)
                  {
                  foreach($results as $result)
                  {
                    $att = $dbh->prepare("SELECT
                        SUM(a.status='attended') AS attended,
                        SUM(a.status='late') AS late,
                        SUM(a.status='absent') AS absent
                        FROM tblclass_attendance a
                        JOIN tblclass_sessions s ON s.id = a.session_id
                        WHERE s.series_id=:series_id AND a.enrollment_id=:enrollment_id");
                    $att->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                    $att->bindParam(':enrollment_id',$result->enrollment_id,PDO::PARAM_INT);
                    $att->execute();
                    $row = $att->fetch(PDO::FETCH_OBJ);
                    $attended = intval($row->attended);
                    $late = intval($row->late);
                    $absent = intval($row->absent);
                    $total = $series->total_sessions;
                    $pct = ($total > 0) ? round((($attended + $late) / $total) * 100) : 0;
                    ?>
	                  <tr>
	                    <td><?php echo htmlentities($result->fname.' '.$result->lname);?></td>
	                    <td><?php echo htmlentities($result->email);?></td>
	                    <td>
	                      <?php
	                      $pst = $result->payment_status;
	                      if($pst == 'paid'){ echo '<span class="label label-success">Paid</span>'; }
	                      elseif($pst == 'pending'){ echo '<span class="label label-warning">Pending</span>'; }
	                      elseif($pst == 'failed'){ echo '<span class="label label-danger">Failed</span>'; }
	                      else { echo htmlentities(ucfirst($pst)); }
	                      ?>
	                    </td>
	                    <td><?php echo $attended;?></td>
	                    <td><?php echo $late;?></td>
	                    <td><?php echo $absent;?></td>
	                    <td><?php echo $total;?></td>
	                    <td><?php echo $pct;?>%</td>
	                  </tr>
	                    <?php } } else {
	                      echo '<tr><td colspan="8">No members enrolled in this series.</td></tr>';
	                    } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
     <?php } ?>
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
