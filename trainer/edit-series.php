<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	require_once '../include/csrf.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	if(isset($_POST['submit'])){
	if (!csrf_verify()) {
	$errormsg= "Invalid request. Please try again.";
	} else {
	$cid = intval($_POST['cid']);
	$title = $_POST['title'];
	$description = $_POST['description'];
	$capacity = intval($_POST['capacity']);
	$price = $_POST['price'];
	$status = intval($_POST['status']);

	$sql="UPDATE tblclass_series SET title=:title,description=:description,capacity=:capacity,price=:price,status=:status WHERE id=:id AND trainer_id=:trainerid";
	$query = $dbh -> prepare($sql);
	$query->bindParam(':title',$title,PDO::PARAM_STR);
	$query->bindParam(':description',$description,PDO::PARAM_STR);
	$query->bindParam(':capacity',$capacity,PDO::PARAM_INT);
	$query->bindParam(':price',$price,PDO::PARAM_STR);
	$query->bindParam(':status',$status,PDO::PARAM_INT);
	$query->bindParam(':id',$cid,PDO::PARAM_INT);
	$query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
	$query -> execute();
	$msg= "Series Updated Successfully";
	}
	}

	$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
	$sql="SELECT * FROM tblclass_series WHERE id=:id AND trainer_id=:trainerid";
	$query= $dbh->prepare($sql);
	$query->bindParam(':id',$cid,PDO::PARAM_INT);
	$query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
	$query-> execute();
	$result = $query->fetch(PDO::FETCH_OBJ);
	if(!$result){
		header('location:manage-series.php');
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Edit Class Series</title>
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
     <h3>Edit Class Series</h3>
     <hr />
      <div class="row">
        <div class="col-md-6">
          <div class="tile">
          <?php if($msg){ ?>
          <div class="alert alert-success" role="alert">
          <strong>Well done!</strong> <?php echo htmlentities($msg);?>
          </div>
          <?php } ?>

              <form class="row" method="post">
                <?php csrf_field(); ?>
                 <input type="hidden" name="cid" value="<?php echo $result->id;?>">
                 <div class="form-group col-md-12">
                  <label class="control-label">Series Title</label>
                  <input class="form-control" name="title" id="title" type="text" value="<?php echo htmlentities($result->title);?>" required>
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Description</label>
                  <textarea class="form-control" name="description" id="description" rows="4"><?php echo htmlentities($result->description);?></textarea>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Total Sessions</label>
                  <input class="form-control" type="text" value="<?php echo htmlentities($result->total_sessions);?>" readonly>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Frequency</label>
                  <input class="form-control" type="text" value="<?php echo htmlentities(ucfirst($result->frequency));?>" readonly>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Start Date</label>
                  <input class="form-control" type="text" value="<?php echo htmlentities($result->start_date);?>" readonly>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Capacity</label>
                  <input class="form-control" name="capacity" id="capacity" type="number" min="1" value="<?php echo htmlentities($result->capacity);?>" required>
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Price (Ksh)</label>
                  <input class="form-control" name="price" id="price" type="number" step="0.01" value="<?php echo htmlentities($result->price);?>">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Status</label>
                  <select class="form-control" name="status" id="status">
                    <option value="1" <?php echo ($result->status==1)?'selected':'';?>>Active</option>
                    <option value="0" <?php echo ($result->status==0)?'selected':'';?>>Inactive</option>
                  </select>
                </div>

                <div class="form-group col-md-4 align-self-end">
                  <input type="submit" name="submit" id="submit" class="btn btn-primary" value=" Update">
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
