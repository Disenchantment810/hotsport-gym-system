<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	require_once '../include/csrf.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	// Update individual session dates
	if(isset($_POST['update_sessions'])){
		if (!csrf_verify()) {
		$errormsg= "Invalid request. Please try again.";
		} else {
		$series_id = intval($_POST['series_id']);
		// verify ownership
		$own = $dbh->prepare("SELECT id FROM tblclass_series WHERE id=:id AND trainer_id=:trainerid");
		$own->bindParam(':id',$series_id,PDO::PARAM_INT);
		$own->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
		$own->execute();
		if($own->rowCount() > 0){
			$dates = $_POST['session_date'];
			$upd = $dbh->prepare("UPDATE tblclass_sessions SET session_date=:session_date WHERE id=:id AND series_id=:series_id");
			foreach($dates as $sid => $sdate){
				if($sdate != ""){
					$upd->bindParam(':session_date',$sdate,PDO::PARAM_STR);
					$upd->bindParam(':id',$sid,PDO::PARAM_INT);
					$upd->bindParam(':series_id',$series_id,PDO::PARAM_INT);
					$upd->execute();
				}
			}
			$msg= "Session dates updated successfully";
		}
		}
	}

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
   <title>Trainer | Manage Sessions</title>
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
     <h3>Manage Sessions</h3>
     <hr />

     <?php if($series_id == 0){ ?>
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
            <h3 class="tile-title">Select a Class Series</h3>
            <div class="tile-body">
              <form class="row" method="get" action="manage-sessions.php">
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
                  <input type="submit" class="btn btn-primary" value="View Sessions">
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
            <h3 class="tile-title">Sessions for: <?php echo htmlentities($series->title);?></h3>
            <?php if($msg){ ?>
            <div class="alert alert-success" role="alert"><strong>Well done!</strong> <?php echo htmlentities($msg);?></div>
            <?php } ?>
            <div class="tile-body">
              <form method="post" action="manage-sessions.php?series=<?php echo $series_id;?>">
                <?php csrf_field(); ?>
                <input type="hidden" name="series_id" value="<?php echo $series_id;?>">
                <table class="table table-hover table-bordered" id="sampleTable">
                  <thead>
                    <tr>
                      <th>Session #</th>
                      <th>Session Date &amp; Time</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php
                    $sql="SELECT * FROM tblclass_sessions WHERE series_id=:series_id ORDER BY session_number";
                    $query= $dbh->prepare($sql);
                    $query->bindParam(':series_id',$series_id,PDO::PARAM_INT);
                    $query-> execute();
                    $results = $query -> fetchAll(PDO::FETCH_OBJ);
                    if($query -> rowCount() > 0)
                    {
                    foreach($results as $result)
                    {
                    ?>
	                  <tr>
	                    <td>Session <?php echo htmlentities($result->session_number);?></td>
	                    <td>
	                      <input class="form-control" type="datetime-local" name="session_date[<?php echo $result->id;?>]" value="<?php echo date('Y-m-d\TH:i', strtotime($result->session_date));?>">
	                    </td>
	                  </tr>
	                    <?php } } ?>
                  </tbody>
                </table>
                <div class="form-group">
                  <input type="submit" name="update_sessions" class="btn btn-primary" value="Update Session Dates">
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
