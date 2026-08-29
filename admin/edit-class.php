<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['adminid'])==0) {
	  header('location:logout.php');
	} else {
	// Class management has moved to the Trainer portal. This admin page is retired.
	header('location:index.php');
	exit;
	$msg = '';
	$errormsg = '';

	$cid=$_GET['cid'];
	if(isset($_POST['Submit'])){
	$title = $_POST['title'];
	$description = $_POST['description'];
	$instructor = $_POST['instructor'];
	$class_date = $_POST['class_date'];
	$duration = $_POST['duration'];
	$capacity = $_POST['capacity'];
	$price = $_POST['price'];
	$image = $_POST['image'];
	$sql="update tblclass set title=:title,description=:description,instructor=:instructor,class_date=:class_date,duration=:duration,capacity=:capacity,price=:price,image=:image where id=:cid";

	$query = $dbh -> prepare($sql);
	$query->bindParam(':title',$title,PDO::PARAM_STR);
	$query->bindParam(':description',$description,PDO::PARAM_STR);
	$query->bindParam(':instructor',$instructor,PDO::PARAM_STR);
	$query->bindParam(':class_date',$class_date,PDO::PARAM_STR);
	$query->bindParam(':duration',$duration,PDO::PARAM_STR);
	$query->bindParam(':capacity',$capacity,PDO::PARAM_INT);
	$query->bindParam(':price',$price,PDO::PARAM_STR);
	$query->bindParam(':image',$image,PDO::PARAM_STR);
	$query->bindParam(':cid',$cid,PDO::PARAM_STR);
	$query -> execute();
	// Mesage after updation
	echo "<script>alert('Record Updated successfully');</script>";
	// Code for redirection
	echo "<script>window.location.href='manage-class.php'</script>";
	}
	}
	?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Form Samples - Vali Admin</title>
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
    <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM SYSTEM | Manage Members, Packages, Bookings and Payments</marquee>

    <main class="app-content">

      <div class="row">

        <div class="col-md-12">
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
            <h3 class="tile-title">Update Class</h3>
               <?php
                   include  'include/config.php';
                  $sql="SELECT * FROM tblclass WHERE id=:cid";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':cid',$cid, PDO::PARAM_STR);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  $cnt=1;
                  if($query -> rowCount() > 0)
                  {
                  foreach($results as $result)
                  {
                  ?>
            <div class="tile-body">
              <form class="row" method="post">
                <div class="form-group col-md-12">
                  <label class="control-label">Class Title</label>
                  <input class="form-control" name="title" id="title" type="text" placeholder="Enter Class Title" value="<?php echo $result->title;?>">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Description</label>
                  <textarea class="form-control" name="description" id="description" rows="4" placeholder="Enter Description"><?php echo $result->description;?></textarea>
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Instructor</label>
                  <input class="form-control" name="instructor" id="instructor" type="text" placeholder="Enter Instructor Name" value="<?php echo $result->instructor;?>">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Class Date & Time</label>
                  <input class="form-control" name="class_date" id="class_date" type="datetime-local" placeholder="Select Date and Time" value="<?php echo $result->class_date;?>">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Duration (minutes)</label>
                  <input class="form-control" name="duration" id="duration" type="number" placeholder="Enter Duration" value="<?php echo $result->duration;?>">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Capacity</label>
                  <input class="form-control" name="capacity" id="capacity" type="number" placeholder="Enter Capacity" value="<?php echo $result->capacity;?>">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Price (Ksh)</label>
                  <input class="form-control" name="price" id="price" type="number" step="0.01" placeholder="Enter Price" value="<?php echo $result->price;?>">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Image URL (optional)</label>
                  <input class="form-control" name="image" id="image" type="text" placeholder="Enter Image URL" value="<?php echo $result->image;?>">
                </div>

                <div class="form-group col-md-4 align-self-end">
                  <input type="Submit" name="Submit" id="Submit" class="btn btn-primary" value="Submit">
                </div>
              </form>
            </div>
             <?php  $cnt=$cnt+1; } } ?>
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
    <!-- The javascript plugin to display page loading on top-->
    <script src="js/plugins/pace.min.js"></script>
    <!-- Page specific javascripts-->
  	<!--

  	- Author Name: MH RONY.
  	- GigHub Link: https://github.com/dev-mhrony
  	- Facebook Link:https://www.facebook.com/dev.mhrony
  	- Youtube Link: <a href = "https://www.youtube.com/@codecampbdofficial"> Code Camp BD</a>
  	- for any PHP, Laravel, Python, Dart, Flutter work contact me at developer.mhrony@gmail.com
  	- Visit My Website : https://dev-mhrony.com
  	 -->
  </body>
</html>