<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	if (strlen($_SESSION['trainerid'])==0) {
	  header('location:login.php');
	  } else {
	$trainerid = $_SESSION['trainerid'];

	// Revoke certificate
	if(isset($_REQUEST['revoke']))
	{
	$cid=intval($_GET['revoke']);
	$sql = "UPDATE tblcertificates SET is_active=0 WHERE id=:id AND trainer_id=:trainerid";
	$query = $dbh->prepare($sql);
	$query-> bindParam(':id',$cid, PDO::PARAM_STR);
	$query-> bindParam(':trainerid',$trainerid, PDO::PARAM_INT);
	$query -> execute();
	echo "<script>alert('Certificate revoked successfully');</script>";
	echo "<script>window.location.href='manage-certificates.php'</script>";
	}

	// Re-activate certificate
	if(isset($_REQUEST['activate']))
	{
	$cid=intval($_GET['activate']);
	$sql = "UPDATE tblcertificates SET is_active=1 WHERE id=:id AND trainer_id=:trainerid";
	$query = $dbh->prepare($sql);
	$query-> bindParam(':id',$cid, PDO::PARAM_STR);
	$query-> bindParam(':trainerid',$trainerid, PDO::PARAM_INT);
	$query -> execute();
	echo "<script>alert('Certificate activated successfully');</script>";
	echo "<script>window.location.href='manage-certificates.php'</script>";
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Trainer | Manage Certificates</title>
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
     <h3>Manage Certificates</h3>
     <hr />
      <div class="row">
        <div class="col-md-12">
          <div class="tile">
            <div class="tile-body">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Sr.No</th>
                    <th>Certificate Code</th>
                    <th>Series</th>
                    <th>Member</th>
                    <th>Completion Date</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
               <?php
                  $sql="SELECT c.*, cs.title AS series_title, u.fname, u.lname
                        FROM tblcertificates c
                        JOIN tblclass_series cs ON cs.id = c.series_id
                        JOIN tbluser u ON u.id = c.user_id
                        WHERE c.trainer_id=:trainerid ORDER BY c.id DESC";
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
	                    <td><?php echo htmlentities($result->certificate_code);?></td>
	                    <td><?php echo htmlentities($result->series_title);?></td>
	                    <td><?php echo htmlentities($result->fname.' '.$result->lname);?></td>
	                    <td><?php echo htmlentities($result->completion_date);?></td>
	                    <td><?php echo ($result->is_active==1) ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Revoked</span>';?></td>
	                    <td>
	                      <?php if($result->is_active==1){ ?>
	                        <a href="manage-certificates.php?revoke=<?php echo htmlentities($result->id);?>" onclick="return confirm('Revoke this certificate?');"><button class="btn btn-danger" type="button">Revoke</button></a>
	                      <?php } else { ?>
	                        <a href="manage-certificates.php?activate=<?php echo htmlentities($result->id);?>" onclick="return confirm('Re-activate this certificate?');"><button class="btn btn-success" type="button">Activate</button></a>
	                      <?php } ?>
	                    </td>
	                  </tr>
	                    <?php  $cnt=$cnt+1; } } else {
	                      echo '<tr><td colspan="7">No certificates issued yet.</td></tr>';
	                    } ?>
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
