<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	// Delete series (only own)
	if(isset($_REQUEST['del']))
	{
	$uid=intval($_GET['del']);
	$sql = "delete from tblclass_series WHERE id=:id AND trainer_id=:trainerid";
	$query = $dbh->prepare($sql);
	$query-> bindParam(':id',$uid, PDO::PARAM_STR);
	$query-> bindParam(':trainerid',$trainerid, PDO::PARAM_INT);
	$query -> execute();
	echo "<script>alert('Series deleted successfully');</script>";
	echo "<script>window.location.href='manage-series.php'</script>";
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Manage Class Series</title>
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
     <h3>Manage Class Series</h3>
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
                    <th>Total Sessions</th>
                    <th>Frequency</th>
                    <th>Start Date</th>
                    <th>Capacity</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
               <?php
                  $sql="SELECT * FROM tblclass_series WHERE trainer_id=:trainerid ORDER BY id DESC";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
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
	                    <td><?php echo htmlentities($result->total_sessions);?></td>
	                    <td><?php echo htmlentities(ucfirst($result->frequency));?></td>
	                    <td><?php echo htmlentities($result->start_date);?></td>
	                    <td><?php echo htmlentities($result->capacity);?></td>
	                    <td><?php echo htmlentities($result->price);?></td>
	                    <td><?php echo ($result->status==1) ? 'Active' : 'Inactive';?></td>
	                    <td>
	                      <a href="manage-sessions.php?series=<?php echo htmlentities($result->id);?>"><button class="btn btn-info" type="button">Sessions</button></a>
	                      <a href="edit-series.php?cid=<?php echo htmlentities($result->id);?>"><button class="btn btn-primary" type="button">Edit</button></a>
	                      <a href="manage-series.php?del=<?php echo htmlentities($result->id);?>" onclick="return confirm('Delete this series and all its sessions?');"><button class="btn btn-danger" type="button">Delete</button></a></td>
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
