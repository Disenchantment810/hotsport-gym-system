<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['adminid'])==0) {
	  header('location:logout.php');
	  } else {
	// Class creation has moved to the Trainer portal. This admin page is retired.
	header('location:index.php');
	exit;
	if(isset($_POST['submit'])){
	$title = $_POST['title'];
	$description = $_POST['description'];
	$instructor = $_POST['instructor'];
	$class_date = $_POST['class_date'];
	$duration = $_POST['duration'];
	$capacity = $_POST['capacity'];
	$price = $_POST['price'];
	$image = $_POST['image'];
	$sql="INSERT INTO tblclass (title,description,instructor,class_date,duration,capacity,price,image) Values(:title,:description,:instructor,:class_date,:duration,:capacity,:price,:image)";
	$query = $dbh -> prepare($sql);
	$query->bindParam(':title',$title,PDO::PARAM_STR);
	$query->bindParam(':description',$description,PDO::PARAM_STR);
	$query->bindParam(':instructor',$instructor,PDO::PARAM_STR);
	$query->bindParam(':class_date',$class_date,PDO::PARAM_STR);
	$query->bindParam(':duration',$duration,PDO::PARAM_STR);
	$query->bindParam(':capacity',$capacity,PDO::PARAM_INT);
	$query->bindParam(':price',$price,PDO::PARAM_STR);
	$query->bindParam(':image',$image,PDO::PARAM_STR);
	$query -> execute();
	$lastInsertId = $dbh->lastInsertId();
	if($lastInsertId>0)
	{
	$msg= "Class Added Successfully";
	echo "<script>window.location.href='add-class.php'</script>";
	 }
	else {
	$errormsg= "Data not insert successfully";
	 }
	}

	//Delete Record Data

	if(isset($_REQUEST['del']))
	{
	$uid=intval($_GET['del']);
	$sql = "delete from tblclass WHERE  id=:id";
	$query = $dbh->prepare($sql);
	$query-> bindParam(':id',$uid, PDO::PARAM_STR);
	$query -> execute();
	echo "<script>alert('Record Delete successfully');</script>";
	echo "<script>window.location.href='add-class.php'</script>";
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Admin | Add Class</title>
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
     <h3>Class Management</h3>
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
                  <label class="control-label">Class Title</label>
                  <input class="form-control" name="title" id="title" type="text" placeholder="Enter Class Title">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Description</label>
                  <textarea class="form-control" name="description" id="description" rows="4" placeholder="Enter Description"></textarea>
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Instructor</label>
                  <input class="form-control" name="instructor" id="instructor" type="text" placeholder="Enter Instructor Name">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Class Date & Time</label>
                  <input class="form-control" name="class_date" id="class_date" type="datetime-local" placeholder="Select Date and Time">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Duration (minutes)</label>
                  <input class="form-control" name="duration" id="duration" type="number" placeholder="Enter Duration">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Capacity</label>
                  <input class="form-control" name="capacity" id="capacity" type="number" placeholder="Enter Capacity">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Price (Ksh)</label>
                  <input class="form-control" name="price" id="price" type="number" step="0.01" placeholder="Enter Price">
                </div>

                <div class="form-group col-md-6">
                  <label class="control-label">Image URL (optional)</label>
                  <input class="form-control" name="image" id="image" type="text" placeholder="Enter Image URL">
                </div>

                <div class="form-group col-md-4 align-self-end">
                  <input type="submit" name="submit" id="submit" class="btn btn-primary" value=" Submit">
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
                    <th>Title</th>
                    <th>Instructor</th>
                    <th>Class Date</th>
                    <th>Duration</th>
                    <th>Capacity</th>
                    <th>Price</th>
                    <th>Action</th>

                  </tr>
                </thead>
               <?php
                  $sql="SELECT * FROM tblclass ORDER BY class_date";
                  $query= $dbh->prepare($sql);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  $cnt=1;
                  if($query -> rowCount() > 0)
                  {
                  foreach($results as $result)
                  {
                  ?>
	                <tbody>
	                  <tr>
	                    <td><?php echo($cnt);?></td>
	                    <td><?php echo htmlentities($result->title);?></td>
	                    <td><?php echo htmlentities($result->instructor);?></td>
	                    <td><?php echo htmlentities($result->class_date);?></td>
	                    <td><?php echo htmlentities($result->duration);?></td>
	                    <td><?php echo htmlentities($result->capacity);?></td>
	                    <td>Ksh <?php echo number_format((float)$result->price, 2);?></td>
	                    <td>
	                      <a href="edit-class.php?cid=<?php echo htmlentities($result->id);?>"><button class="btn btn-primary" type="button">Edit</button></a>
	                      <a href="add-class.php?del=<?php echo htmlentities($result->id);?>"><button class="btn btn-danger" type="button">Delete</button></a></td>
	                  </tr>
	                    <?php  $cnt=$cnt+1; } } ?>

                </tbody>
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
    <!-- The javascript plugin to display page loading on top-->
    <script src="js/plugins/pace.min.js"></script>
    <!-- Page specific javascripts-->
    <!-- Data table plugin-->
    <script type="text/javascript" src="js/plugins/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="js/plugins/dataTables.bootstrap.min.js"></script>
    <script type="text/javascript">$('#sampleTable').DataTable();</script>
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