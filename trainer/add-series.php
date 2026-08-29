<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	if(isset($_POST['submit'])){
	$title = $_POST['title'];
	$description = $_POST['description'];
	$total_sessions = intval($_POST['total_sessions']);
	$frequency = $_POST['frequency'];
	$start_date = $_POST['start_date'];
	$capacity = intval($_POST['capacity']);
	$price = $_POST['price'];
	$status = intval($_POST['status']);

	if($total_sessions > 0 && $start_date != ""){
		$sql="INSERT INTO tblclass_series (trainer_id,title,description,total_sessions,frequency,start_date,capacity,price,status) Values(:trainer_id,:title,:description,:total_sessions,:frequency,:start_date,:capacity,:price,:status)";
		$query = $dbh -> prepare($sql);
		$query->bindParam(':trainer_id',$trainerid,PDO::PARAM_INT);
		$query->bindParam(':title',$title,PDO::PARAM_STR);
		$query->bindParam(':description',$description,PDO::PARAM_STR);
		$query->bindParam(':total_sessions',$total_sessions,PDO::PARAM_INT);
		$query->bindParam(':frequency',$frequency,PDO::PARAM_STR);
		$query->bindParam(':start_date',$start_date,PDO::PARAM_STR);
		$query->bindParam(':capacity',$capacity,PDO::PARAM_INT);
		$query->bindParam(':price',$price,PDO::PARAM_STR);
		$query->bindParam(':status',$status,PDO::PARAM_INT);
		$query -> execute();
		$series_id = $dbh->lastInsertId();

		// Auto-generate session records based on frequency
		$intervalMap = array('daily'=>'P1D','weekly'=>'P1W','biweekly'=>'P2W','monthly'=>'P1M');
		$interval = isset($intervalMap[$frequency]) ? $intervalMap[$frequency] : 'P1W';
		$date = new DateTime($start_date);
		$step = new DateInterval($interval);
		$ins = $dbh->prepare("INSERT INTO tblclass_sessions (series_id, session_number, session_date) VALUES (:series_id, :session_number, :session_date)");
		for($i = 1; $i <= $total_sessions; $i++){
			$ins->bindParam(':series_id',$series_id,PDO::PARAM_INT);
			$ins->bindParam(':session_number',$i,PDO::PARAM_INT);
			$sd = $date->format('Y-m-d H:i:s');
			$ins->bindParam(':session_date',$sd,PDO::PARAM_STR);
			$ins->execute();
			$date->add($step);
		}
		$msg= "Class Series Added Successfully with ".$total_sessions." sessions";
		echo "<script>window.location.href='manage-sessions.php?series=".$series_id."'</script>";
	} else {
		$errormsg= "Total sessions and start date are required";
	}
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Add Class Series</title>
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
     <h3>Add Class Series</h3>
     <hr />
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
          <!---Success Message--->
          <?php if($msg){ ?>
          <div class="alert alert-success" role="alert">
          <strong>Well done!</strong> <?php echo htmlentities($msg);?>
          </div>
          <?php } ?>

          <!---Error Message--->
          <?php if($errormsg){ ?>
          <div class="alert alert-danger" role="alert">
          <strong>Oh snap!</strong> <?php echo htmlentities($errormsg);?></div>
          <?php } ?>

              <form class="row" method="post">
                 <div class="form-group col-md-12">
                  <label class="control-label">Series Title</label>
                  <input class="form-control" name="title" id="title" type="text" placeholder="Enter Series Title" required>
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Description</label>
                  <textarea class="form-control" name="description" id="description" rows="4" placeholder="Enter Description"></textarea>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Total Sessions</label>
                  <input class="form-control" name="total_sessions" id="total_sessions" type="number" min="1" placeholder="e.g. 10" required>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Frequency</label>
                  <select class="form-control" name="frequency" id="frequency">
                    <option value="daily">Daily</option>
                    <option value="weekly" selected>Weekly</option>
                    <option value="biweekly">Bi-Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Start Date</label>
                  <input class="form-control" name="start_date" id="start_date" type="date" required>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Capacity</label>
                  <input class="form-control" name="capacity" id="capacity" type="number" min="1" placeholder="Enter Capacity" required>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Price (Ksh)</label>
                  <input class="form-control" name="price" id="price" type="number" step="0.01" placeholder="Enter Price">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Status</label>
                  <select class="form-control" name="status" id="status">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>

                <div class="form-group col-md-4 align-self-end">
                  <input type="submit" name="submit" id="submit" class="btn btn-primary" value=" Submit">
                </div>
              </form>
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
  </body>
</html>
