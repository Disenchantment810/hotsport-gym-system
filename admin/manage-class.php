<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['adminid'])==0) {
	  header('location:logout.php');
	  }
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Admin | Manage Class</title>
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
     <h3>Manage Classes</h3>
     <hr />
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
	                    <td><?php echo htmlentities($result->price);?></td>
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