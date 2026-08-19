<?php  session_start();
error_reporting(0);
include  'include/config.php';
if (strlen($_SESSION['adminid']==0)) {
  header('location:logout.php');
  } else{
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="description" content="Vali is a">
   <title>admin | Attendance Report</title>
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
              <h3>Attendance Report</h3>
              <hr />

              <!-- Filter Form -->
              <form method="post" action="" class="mb-4">
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="from_date">From Date:</label>
                      <input type="text" class="form-control datepicker" id="from_date" name="from_date" placeholder="Select start date">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="to_date">To Date:</label>
                      <input type="text" class="form-control datepicker" id="to_date" name="to_date" placeholder="Select end date">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label for="user_filter">Member:</label>
                      <select class="form-control" id="user_filter" name="user_filter">
                        <option value="">-- All Members --</option>
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
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>&nbsp;</label><br/>
                      <button type="submit" name="filter_submit" class="btn btn-primary btn-block">Filter</button>
                      <button type="submit" name="reset_submit" class="btn btn-default btn-block">Reset</button>
                    </div>
                  </div>
                </div>
              </form>

              <?php
              // Build query based on filters
              $where = "";
              $params = array();

              if(isset($_POST['filter_submit']))
              {
                  // Date range filter
                  if(!empty($_POST['from_date']))
                  {
                      $where .= " AND DATE(a.check_in) >= :from_date";
                      $params[':from_date'] = $_POST['from_date'];
                  }
                  if(!empty($_POST['to_date']))
                  {
                      $where .= " AND DATE(a.check_in) <= :to_date";
                      $params[':to_date'] = $_POST['to_date'];
                  }

                  // User filter
                  if(!empty($_POST['user_filter']) && is_numeric($_POST['user_filter']))
                  {
                      $where .= " AND a.user_id = :user_id";
                      $params[':user_id'] = $_POST['user_filter'];
                  }
              }

              // Main query
              $sql = "SELECT a.id, u.fname, u.lname, a.check_in, a.check_out,
                             TIMESTAMPDIFF(HOUR, a.check_in,
                                 CASE WHEN a.check_out IS NULL THEN NOW() ELSE a.check_out END) as hours_spent
                      FROM tblattendance a
                      JOIN tbluser u ON a.user_id = u.id
                      WHERE 1=1 ".$where."
                      ORDER BY a.check_in DESC";

              $query = $dbh->prepare($sql);

              // Bind parameters
              foreach($params as $key => $value)
              {
                  $query->bindParam($key, $value);
              }

              $query->execute();
              $results = $query->fetchAll(PDO::FETCH_OBJ);
              ?>

              <!-- Attendance Records Table -->
              <div class="table-responsive">
                <table class="table table-hover table-bordered" id="sampleTable">
                  <thead>
                    <tr>
                      <th>Sr.No</th>
                      <th>Member Name</th>
                      <th>Check-In Time</th>
                      <th>Check-Out Time</th>
                      <th>Hours Spent</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if($query->rowCount() > 0)
                    {
                        $cnt=1;
                        foreach($results as $result)
                        {
                        ?>
                        <tr>
                          <td><?php echo($cnt);?></td>
                          <td><?php echo htmlentities($result->fname . ' ' . $result->lname);?></td>
                          <td><?php echo htmlentities($result->check_in);?></td>
                          <td>
                            <?php
                            if($result->check_out)
                            {
                                echo htmlentities($result->check_out);
                            }
                            else
                            {
                                echo "<span class='text-warning'>Still Checked In</span>";
                            }
                            ?>
                          </td>
                          <td><?php echo htmlentities(round($result->hours_spent, 2));?> hrs</td>
                        </tr>
                        <?php  $cnt=$cnt+1; } ?>
                    }
                    else
                    {
                    ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted">No attendance records found for the selected criteria.</td>
                    </tr>
                    <?php
                    }
                    ?>
                  </tbody>
                </table>
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
    <script>
        // Initialize datepickers
        $(document).ready(function(){
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
            });
        });
    </script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="js/plugins/pace.min.js"></script>
  </body>
</html>
<?php } ?>