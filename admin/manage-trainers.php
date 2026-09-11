<?php session_start();
	error_reporting(0);
	include  'include/config.php';
	require_once '../include/csrf.php';
	require_once '../include/password_migration.php';
	if (strlen($_SESSION['adminid'])==0) {
	  header('location:logout.php');
	  } else {

	// Add / Update trainer
	if(isset($_POST['submit'])){
	if (!csrf_verify()) {
	$errormsg= "Invalid request. Please try again.";
	} else {
	$name = $_POST['name'];
	$email = $_POST['email'];
	$mobile = $_POST['mobile'];
	$specialization = $_POST['specialization'];
	$bio = $_POST['bio'];
	$status = $_POST['status'];
	$editid = isset($_POST['editid']) ? $_POST['editid'] : 0;

	if($editid > 0){
		$sql="UPDATE tbltrainers SET name=:name,email=:email,mobile=:mobile,specialization=:specialization,bio=:bio,status=:status WHERE id=:id";
		$query = $dbh -> prepare($sql);
		$query->bindParam(':name',$name,PDO::PARAM_STR);
		$query->bindParam(':email',$email,PDO::PARAM_STR);
		$query->bindParam(':mobile',$mobile,PDO::PARAM_STR);
		$query->bindParam(':specialization',$specialization,PDO::PARAM_STR);
		$query->bindParam(':bio',$bio,PDO::PARAM_STR);
		$query->bindParam(':status',$status,PDO::PARAM_INT);
		$query->bindParam(':id',$editid,PDO::PARAM_INT);
		$query -> execute();
		$msg= "Trainer Updated Successfully";
	} else {
		$password = password_hash_new($_POST['password']);
		$sql="INSERT INTO tbltrainers (name,email,mobile,password,specialization,bio,status) Values(:name,:email,:mobile,:password,:specialization,:bio,:status)";
		$query = $dbh -> prepare($sql);
		$query->bindParam(':name',$name,PDO::PARAM_STR);
		$query->bindParam(':email',$email,PDO::PARAM_STR);
		$query->bindParam(':mobile',$mobile,PDO::PARAM_STR);
		$query->bindParam(':password',$password,PDO::PARAM_STR);
		$query->bindParam(':specialization',$specialization,PDO::PARAM_STR);
		$query->bindParam(':bio',$bio,PDO::PARAM_STR);
		$query->bindParam(':status',$status,PDO::PARAM_INT);
		$query -> execute();
		$lastInsertId = $dbh->lastInsertId();
		if($lastInsertId>0)
		{
		$msg= "Trainer Added Successfully";
		}
		else {
		$errormsg= "Data not insert successfully";
		}
	}
	}
	}

	// Delete trainer
	if(isset($_POST['del']))
	{
	if (!csrf_verify()) {
	echo "<script>alert('Invalid request. Please try again.');</script>";
	} else {
	$uid=intval($_POST['del']);
	$sql = "delete from tbltrainers WHERE  id=:id";
	$query = $dbh->prepare($sql);
	$query-> bindParam(':id',$uid, PDO::PARAM_STR);
	$query -> execute();
	echo "<script>alert('Record Delete successfully');</script>";
	echo "<script>window.location.href='manage-trainers.php'</script>";
	}
	}

	// Load trainer for edit
	$editrow = null;
	if(isset($_REQUEST['eid']))
	{
	$eid=intval($_GET['eid']);
	$sql = "SELECT * FROM tbltrainers WHERE id=:id";
	$query = $dbh->prepare($sql);
	$query-> bindParam(':id',$eid, PDO::PARAM_STR);
	$query -> execute();
	$editrow = $query->fetch(PDO::FETCH_OBJ);
	}
	}
	?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>Admin | Manage Trainers</title>
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
     <h3>Manage Trainers</h3>
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
                 <?php csrf_field(); ?>
                 <div class="form-group col-md-12">
                  <label class="control-label">Trainer Name</label>
                  <input class="form-control" name="name" id="name" type="text" placeholder="Enter Trainer Name" value="<?php echo $editrow ? htmlentities($editrow->name) : '';?>">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Email</label>
                  <input class="form-control" name="email" id="email" type="email" placeholder="Enter Email" value="<?php echo $editrow ? htmlentities($editrow->email) : '';?>">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Mobile</label>
                  <input class="form-control" name="mobile" id="mobile" type="text" placeholder="Enter Mobile" value="<?php echo $editrow ? htmlentities($editrow->mobile) : '';?>">
                </div>

                <?php if(!$editrow){ ?>
                <div class="form-group col-md-12">
                  <label class="control-label">Password</label>
                  <input class="form-control" name="password" id="password" type="password" placeholder="Enter Password">
                </div>
                <?php } ?>

                <div class="form-group col-md-12">
                  <label class="control-label">Specialization</label>
                  <input class="form-control" name="specialization" id="specialization" type="text" placeholder="Enter Specialization" value="<?php echo $editrow ? htmlentities($editrow->specialization) : '';?>">
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Bio</label>
                  <textarea class="form-control" name="bio" id="bio" rows="3" placeholder="Enter Bio"><?php echo $editrow ? htmlentities($editrow->bio) : '';?></textarea>
                </div>

                <div class="form-group col-md-12">
                  <label class="control-label">Status</label>
                  <select class="form-control" name="status" id="status">
                    <option value="1" <?php echo ($editrow && $editrow->status==1) ? 'selected' : '';?>>Active</option>
                    <option value="0" <?php echo ($editrow && $editrow->status==0) ? 'selected' : '';?>>Inactive</option>
                  </select>
                </div>

                <?php if($editrow){ ?>
                <input type="hidden" name="editid" value="<?php echo $editrow->id;?>">
                <?php } ?>

                <div class="form-group col-md-4 align-self-end">
                  <input type="submit" name="submit" id="submit" class="btn btn-primary" value="<?php echo $editrow ? 'Update' : 'Submit';?>">
                  <?php if($editrow){ ?><a href="manage-trainers.php" class="btn btn-secondary">Cancel</a><?php } ?>
                </div>
              </form>
            </div>
          </div>

        <div class="col-md-6">
          <div class="tile">
            <div class="tile-body">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th>Sr.No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
               <?php
                  $sql="SELECT * FROM tbltrainers ORDER BY id DESC";
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
	                    <td><?php echo htmlentities($result->name);?></td>
	                    <td><?php echo htmlentities($result->email);?></td>
	                    <td><?php echo htmlentities($result->mobile);?></td>
	                    <td><?php echo ($result->status==1) ? 'Active' : 'Inactive';?></td>
	                    <td>
	                      <a href="manage-trainers.php?eid=<?php echo htmlentities($result->id);?>"><button class="btn btn-primary" type="button">Edit</button></a>
	                      <form method="post" style="display:inline;" onsubmit="return confirm('Delete this trainer?');">
	                        <input type="hidden" name="csrf_token" value="<?php echo htmlentities(csrf_token()); ?>">
	                        <input type="hidden" name="del" value="<?php echo htmlentities($result->id); ?>">
	                        <button type="submit" class="btn btn-danger">Delete</button>
	                      </form></td>
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
  </body>
</html>
