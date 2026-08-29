<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	$series_id = isset($_GET['series']) ? intval($_GET['series']) : 0;

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

	// Issue certificate
	if(isset($_POST['issue']) && $series){
		$enrollment_id = intval($_POST['enrollment_id']);
		$user_id = intval($_POST['user_id']);
		// Verify this enrollment belongs to this series
		$chk = $dbh->prepare("SELECT id FROM tblclass_enrollment WHERE id=:id AND series_id=:series_id");
		$chk->bindParam(':id',$enrollment_id,PDO::PARAM_INT);
		$chk->bindParam(':series_id',$series_id,PDO::PARAM_INT);
		$chk->execute();
		if($chk->rowCount() > 0){
			// Check not already issued
			$already = $dbh->prepare("SELECT id FROM tblcertificates WHERE series_id=:series_id AND user_id=:user_id");
			$already->bindParam(':series_id',$series_id,PDO::PARAM_INT);
			$already->bindParam(':user_id',$user_id,PDO::PARAM_INT);
			$already->execute();
			if($already->rowCount() == 0){
				$code = 'HSP-'.strtoupper(bin2hex(random_bytes(6)));
				$completion_date = date('Y-m-d');
				$ins = $dbh->prepare("INSERT INTO tblcertificates (certificate_code, series_id, user_id, trainer_id, completion_date, is_active) VALUES (:code, :series_id, :user_id, :trainer_id, :completion_date, 1)");
				$ins->bindParam(':code',$code,PDO::PARAM_STR);
				$ins->bindParam(':series_id',$series_id,PDO::PARAM_INT);
				$ins->bindParam(':user_id',$user_id,PDO::PARAM_INT);
				$ins->bindParam(':trainer_id',$trainerid,PDO::PARAM_INT);
				$ins->bindParam(':completion_date',$completion_date,PDO::PARAM_STR);
				$ins->execute();
				$msg= "Certificate issued successfully. Code: ".$code;
			} else {
				$errormsg= "A certificate has already been issued for this member.";
			}
		}
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Issue Certificates</title>
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
     <h3>Issue Certificates</h3>
     <hr />

     <?php if($series_id == 0){ ?>
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
            <h3 class="tile-title">Select a Class Series</h3>
            <div class="tile-body">
              <form class="row" method="get" action="certificates.php">
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
                  <input type="submit" class="btn btn-primary" value="View Members">
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
            <h3 class="tile-title">Certificate Eligibility for: <?php echo htmlentities($series->title);?></h3>
            <?php if($msg){ ?>
            <div class="alert alert-success" role="alert"><strong>Well done!</strong> <?php echo htmlentities($msg);?></div>
            <?php } ?>
            <?php if($errormsg){ ?>
            <div class="alert alert-danger" role="alert"><strong>Oh snap!</strong> <?php echo htmlentities($errormsg);?></div>
            <?php } ?>
            <div class="tile-body">
              <p>Eligibility threshold: 80% of <?php echo $series->total_sessions;?> sessions (attended + late).</p>
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Email</th>
                    <th>Attendance</th>
                    <th>%</th>
                    <th>Eligible</th>
                    <th>Certificate</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                  $sql="SELECT e.id AS enrollment_id, u.id AS user_id, u.fname, u.lname, u.email
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
                    // On-demand attendance count
                    $att = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_attendance a
                        JOIN tblclass_sessions s ON s.id = a.session_id
                        WHERE s.series_id=:series_id AND a.enrollment_id=:enrollment_id AND a.status IN ('attended','late')");
                    $att->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                    $att->bindParam(':enrollment_id',$result->enrollment_id,PDO::PARAM_INT);
                    $att->execute();
                    $attended = $att->fetch(PDO::FETCH_OBJ)->c;
                    $total = $series->total_sessions;
                    $pct = ($total > 0) ? round(($attended / $total) * 100) : 0;
                    $eligible = ($pct >= 80);

                    // Existing certificate
                    $cert = $dbh->prepare("SELECT certificate_code, is_active FROM tblcertificates WHERE series_id=:series_id AND user_id=:user_id");
                    $cert->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                    $cert->bindParam(':user_id',$result->user_id,PDO::PARAM_INT);
                    $cert->execute();
                    $certrow = $cert->fetch(PDO::FETCH_OBJ);
                    ?>
	                  <tr>
	                    <td><?php echo htmlentities($result->fname.' '.$result->lname);?></td>
	                    <td><?php echo htmlentities($result->email);?></td>
	                    <td><?php echo $attended;?> / <?php echo $total;?></td>
	                    <td><?php echo $pct;?>%</td>
	                    <td><?php echo $eligible ? '<span class="label label-success">Eligible</span>' : '<span class="label label-default">Not yet</span>';?></td>
	                    <td>
	                      <?php if($certrow){ echo htmlentities($certrow->certificate_code).' ('.($certrow->is_active==1?'Active':'Revoked').')'; } else { echo '--'; } ?>
	                    </td>
	                    <td>
	                      <?php if($eligible && !$certrow){ ?>
	                        <form method="post" style="display:inline;">
	                          <input type="hidden" name="enrollment_id" value="<?php echo $result->enrollment_id;?>">
	                          <input type="hidden" name="user_id" value="<?php echo $result->user_id;?>">
	                          <button type="submit" name="issue" class="btn btn-sm btn-success">Issue Certificate</button>
	                        </form>
	                      <?php } else { echo '--'; } ?>
	                    </td>
	                  </tr>
	                    <?php } } else {
	                      echo '<tr><td colspan="7">No members enrolled in this series.</td></tr>';
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
