<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	require_once '../include/csrf.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	$series_id = isset($_GET['series']) ? intval($_GET['series']) : 0;
	$session_id = isset($_GET['session']) ? intval($_GET['session']) : 0;

	// Verify series ownership
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

	// Verify session belongs to owned series
	$session = null;
	if($session_id > 0 && $series_id > 0){
		$sql="SELECT * FROM tblclass_sessions WHERE id=:id AND series_id=:series_id";
		$query= $dbh->prepare($sql);
		$query->bindParam(':id',$session_id,PDO::PARAM_INT);
		$query->bindParam(':series_id',$series_id,PDO::PARAM_INT);
		$query-> execute();
		$session = $query->fetch(PDO::FETCH_OBJ);
		if(!$session){ $session_id = 0; }
	}

	// Save attendance (upsert)
	if(isset($_POST['save_attendance']) && $session){
		if (!csrf_verify()) {
		$errormsg= "Invalid request. Please try again.";
		} else {
		$statuses = $_POST['attendance'];
		$upd = $dbh->prepare("INSERT INTO tblclass_attendance (session_id, enrollment_id, status) VALUES (:session_id, :enrollment_id, :status)
			ON DUPLICATE KEY UPDATE status=:status2");
		foreach($statuses as $enrollment_id => $status){
			if(in_array($status, array('attended','absent','late'))){
				$upd->bindParam(':session_id',$session_id,PDO::PARAM_INT);
				$upd->bindParam(':enrollment_id',$enrollment_id,PDO::PARAM_INT);
				$upd->bindParam(':status',$status,PDO::PARAM_STR);
				$upd->bindParam(':status2',$status,PDO::PARAM_STR);
				$upd->execute();
			}
		}
		$msg= "Attendance saved successfully";
		}
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Mark Attendance</title>
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
     <h3>Mark Attendance</h3>
     <hr />

     <?php if($series_id == 0){ ?>
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
            <h3 class="tile-title">Step 1: Select a Class Series</h3>
            <div class="tile-body">
              <form class="row" method="get" action="attendance.php">
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
                  <input type="submit" class="btn btn-primary" value="Next">
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
     <?php } elseif($session_id == 0){ ?>
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
            <h3 class="tile-title">Step 2: Select a Session for: <?php echo htmlentities($series->title);?></h3>
            <div class="tile-body">
              <form class="row" method="get" action="attendance.php">
                <input type="hidden" name="series" value="<?php echo $series_id;?>">
                <div class="form-group col-md-12">
                  <label class="control-label">Session</label>
                  <select class="form-control" name="session" required>
                    <option value="">-- Select Session --</option>
                    <?php
                      $sql="SELECT * FROM tblclass_sessions WHERE series_id=:series_id ORDER BY session_number";
                      $query= $dbh->prepare($sql);
                      $query->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                      $query-> execute();
                      $results = $query -> fetchAll(PDO::FETCH_OBJ);
                      foreach($results as $result){ ?>
                        <option value="<?php echo $result->id;?>">Session <?php echo $result->session_number;?> - <?php echo date('d M Y H:i', strtotime($result->session_date));?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group col-md-4 align-self-end">
                  <input type="submit" class="btn btn-primary" value="Next">
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
            <h3 class="tile-title">Attendance for: <?php echo htmlentities($series->title);?> - Session <?php echo $session->session_number;?> (<?php echo date('d M Y H:i', strtotime($session->session_date));?>)</h3>
            <?php if($msg){ ?>
            <div class="alert alert-success" role="alert"><strong>Well done!</strong> <?php echo htmlentities($msg);?></div>
            <?php } ?>
            <div class="tile-body">
              <form method="post" action="attendance.php?series=<?php echo $series_id;?>&session=<?php echo $session_id;?>">
                <?php csrf_field(); ?>
                <table class="table table-hover table-bordered" id="sampleTable">
                  <thead>
                    <tr>
                      <th>Member</th>
                      <th>Email</th>
                      <th>Attendance</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $sql="SELECT e.id AS enrollment_id, u.fname, u.lname, u.email, a.status AS att_status
                          FROM tblclass_enrollment e
                          JOIN tbluser u ON u.id = e.user_id
                          LEFT JOIN tblclass_attendance a ON a.enrollment_id = e.id AND a.session_id = :session_id
                          WHERE e.series_id = :series_id ORDER BY u.fname";
                    $query= $dbh->prepare($sql);
                    $query->bindParam(':session_id',$session_id,PDO::PARAM_INT);
                    $query->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                    $query-> execute();
                    $results = $query -> fetchAll(PDO::FETCH_OBJ);
                    if($query -> rowCount() > 0)
                    {
                    foreach($results as $result)
                    {
                    $cur = $result->att_status ? $result->att_status : 'absent';
                    ?>
	                  <tr>
	                    <td><?php echo htmlentities($result->fname.' '.$result->lname);?></td>
	                    <td><?php echo htmlentities($result->email);?></td>
	                    <td>
	                      <select class="form-control" name="attendance[<?php echo $result->enrollment_id;?>]">
	                        <option value="attended" <?php echo ($cur=='attended')?'selected':'';?>>Attended</option>
	                        <option value="late" <?php echo ($cur=='late')?'selected':'';?>>Late</option>
	                        <option value="absent" <?php echo ($cur=='absent')?'selected':'';?>>Absent</option>
	                      </select>
	                    </td>
	                  </tr>
	                    <?php } } else {
	                      echo '<tr><td colspan="3">No members enrolled in this series.</td></tr>';
	                    } ?>
                  </tbody>
                </table>
                <div class="form-group">
                  <input type="submit" name="save_attendance" class="btn btn-primary" value="Save Attendance">
                </div>
              </form>
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
