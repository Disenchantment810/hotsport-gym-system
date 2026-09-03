<?php  session_start();
error_reporting(0);
include  'include/config.php';
require_once '../include/csrf.php';
if (strlen($_SESSION['adminid'])==0) {
  header('location:logout.php');
  } else{
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>admin | Attendance Check In/Out</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Datepicker CSS-->
    <link rel="stylesheet" href="js/plugins/bootstrap-datepicker.min.css">
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
            <div class="tile-body">
              <h3>Member Attendance Check In/Out</h3>
              <hr />

              <?php
              // Handle check-in form submission
              if(isset($_POST['checkin_submit']))
              {
                  if (!csrf_verify()) {
                      echo "<div class='alert alert-danger'>Invalid request. Please try again.</div>";
                  } else {
                  $user_id = $_POST['user_id'];

                  // Check if user is already checked in (has an open session)
                  $check_sql = "SELECT id FROM tblattendance WHERE user_id = :user_id AND check_out IS NULL";
                  $check_query = $dbh->prepare($check_sql);
                  $check_query->bindParam(':user_id',$user_id,PDO::PARAM_INT);
                  $check_query->execute();

                  if($check_query->rowCount() > 0)
                  {
                      echo "<div class='alert alert-danger'>Member is already checked in. Please check them out first.</div>";
                  }
                  else
                  {
                      // Insert check-in record
                      $sql = "INSERT INTO tblattendance (user_id, check_in) VALUES (:user_id, NOW())";
                      $query = $dbh->prepare($sql);
                      $query->bindParam(':user_id',$user_id,PDO::PARAM_INT);
                      $query->execute();

                      if($query)
                      {
                          echo "<div class='alert alert-success'>Member checked in successfully.</div>";
                      }
                      else
                      {
                          echo "<div class='alert alert-danger'>Error checking in member.</div>";
                      }
                  }
                  }
              }

              // Handle check-out button submission
              if(isset($_POST['checkout_submit']))
              {
                  if (!csrf_verify()) {
                      echo "<div class='alert alert-danger'>Invalid request. Please try again.</div>";
                  } else {
                  $attendance_id = $_POST['attendance_id'];

                  // Update check-out time
                  $sql = "UPDATE tblattendance SET check_out = NOW() WHERE id = :attendance_id AND check_out IS NULL";
                  $query = $dbh->prepare($sql);
                  $query->bindParam(':attendance_id',$attendance_id,PDO::PARAM_INT);
                  $query->execute();

                  if($query)
                  {
                      echo "<div class='alert alert-success'>Member checked out successfully.</div>";
                  }
                  else
                  {
                      echo "<div class='alert alert-danger'>Error checking out member.</div>";
                  }
                  }
              }
              ?>

              <!-- Check-In Form -->
              <div class="row mb-4">
                <div class="col-md-6">
                  <form method="post" action="">
                    <?php csrf_field(); ?>
                    <div class="form-group">
                      <label for="user_id">Select Member:</label>
                      <select class="form-control" id="user_id" name="user_id" required>
                        <option value="">-- Select Member --</option>
                        <?php
                        $sql = "SELECT id, CONCAT(fname, ' ', lname) as name FROM tbluser ORDER BY fname, lname";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $results = $query->fetchAll(PDO::FETCH_OBJ);

                        if($query->rowCount() > 0)
                        {
                            foreach($results as $result)
                            {
                        ?>
                        <option value="<?php echo htmlentities($result->id);?>"><?php echo htmlentities($result->name);?></option>
                        <?php
                            }
                        }
                        ?>
                      </select>
                    </div>
                    <button type="submit" name="checkin_submit" class="btn btn-primary">Check In Member</button>
                  </form>
                </div>
              </div>

              <!-- Currently Checked-In Members -->
              <div class="row">
                <div class="col-md-12">
                  <h4>Currently Checked-In Members</h4>
                  <?php
                  $sql = "SELECT a.id, u.fname, u.lname, a.check_in
                          FROM tblattendance a
                          JOIN tbluser u ON a.user_id = u.id
                          WHERE a.check_out IS NULL
                          ORDER BY a.check_in DESC";
                  $query = $dbh->prepare($sql);
                  $query->execute();
                  $results = $query->fetchAll(PDO::FETCH_OBJ);

                  if($query->rowCount() > 0)
                  {
                  ?>
                  <table class="table table-hover table-bordered" id="sampleTable">
                    <thead>
                      <tr>
                        <th>Sr.No</th>
                        <th>Member Name</th>
                        <th>Check-In Time</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $cnt=1;
                      foreach($results as $result)
                      {
                      ?>
                      <tr>
                        <td><?php echo($cnt);?></td>
                        <td><?php echo htmlentities($result->fname . ' ' . $result->lname);?></td>
                        <td><?php echo htmlentities($result->check_in);?></td>
                        <td>
                          <form method="post" action="" style="display: inline;">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="attendance_id" value="<?php echo htmlentities($result->id);?>">
                            <button type="submit" name="checkout_submit" class="btn btn-warning btn-sm">Check Out</button>
                          </form>
                        </td>
                      </tr>
                      <?php  $cnt=$cnt+1; } ?>
                    </tbody>
                  </table>
                  <?php
                  }
                  else
                  {
                      echo "<p class='text-muted'>No members currently checked in.</p>";
                  }
                  ?>
                </div>
              </div>
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
    <!-- Datepicker JS-->
    <script src="js/plugins/bootstrap-datepicker.min.js"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="js/plugins/pace.min.js"></script>
  </body>
</html>
<?php } ?>