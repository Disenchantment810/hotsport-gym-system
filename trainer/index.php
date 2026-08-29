<?php  session_start();
error_reporting(0);
include  'include/config.php';
if (strlen($_SESSION['trainerid'])==0) {
  header('location:login.php');
  } else{
  $trainerid = $_SESSION['trainerid'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Trainer | Dashboard</title>
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
    <main class="app-content">
      <div class="app-title">
        <div>
          <h1><i class="fa fa-dashboard"></i> Dashboard</h1>
        </div>
        <ul class="app-breadcrumb breadcrumb">
          <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
          <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
        </ul>
      </div>
      <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM SYSTEM | Trainer Portal</marquee>

      <div class="row">
        <div class="col-md-6 col-lg-6">
          <?php
                  $sql="SELECT count(id) as total FROM tblclass_series WHERE trainer_id=:trainerid;";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  foreach($results as $result)
                  {
                  ?>
                       <a href="manage-series.php">
          <div class="widget-small info coloured-icon"><i class="icon fa fa-calendar fa-3x"></i>
            <div class="info">
              <h4>My Class Series</h4>
              <p><b><?php echo $result->total;?></b></p>
            </div>
          </div></a>
            <?php  } ?>
        </div>

        <div class="col-md-6 col-lg-6">
          <?php
                  $sql="SELECT count(s.id) as total FROM tblclass_sessions s JOIN tblclass_series cs ON cs.id=s.series_id WHERE cs.trainer_id=:trainerid;";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  foreach($results as $result)
                  {
                  ?>
                       <a href="manage-sessions.php">
          <div class="widget-small primary coloured-icon"><i class="icon fa fa-list-alt fa-3x"></i>
            <div class="info">
              <h4>My Sessions</h4>
              <p><b><?php echo $result->total;?></b></p>
            </div>
          </div></a>
            <?php  } ?>
        </div>

        <div class="col-md-6 col-lg-6">
          <?php
                  $sql="SELECT count(e.id) as total FROM tblclass_enrollment e JOIN tblclass_series cs ON cs.id=e.series_id WHERE cs.trainer_id=:trainerid;";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  foreach($results as $result)
                  {
                  ?>
                       <a href="attendance.php">
          <div class="widget-small warning coloured-icon"><i class="icon fa fa-users fa-3x"></i>
            <div class="info">
              <h4>My Enrollments</h4>
              <p><b><?php echo $result->total;?></b></p>
            </div>
          </div></a>
            <?php  } ?>
        </div>

        <div class="col-md-6 col-lg-6">
          <?php
                  $sql="SELECT count(id) as total FROM tblcertificates WHERE trainer_id=:trainerid;";
                  $query= $dbh->prepare($sql);
                  $query->bindParam(':trainerid',$trainerid,PDO::PARAM_INT);
                  $query-> execute();
                  $results = $query -> fetchAll(PDO::FETCH_OBJ);
                  foreach($results as $result)
                  {
                  ?>
                       <a href="certificates.php">
          <div class="widget-small danger coloured-icon"><i class="icon fa fa-certificate fa-3x"></i>
            <div class="info">
              <h4>My Certificates</h4>
              <p><b><?php echo $result->total;?></b></p>
            </div>
          </div></a>
            <?php  } ?>
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
<?php } ?>
