<?php
session_start();
error_reporting(0);
require_once('include/config.php');

if (strlen($_SESSION['adminid']) == 0) {
    header('location:logout.php');
    exit;
} else {
    $msg = '';
    $errormsg = '';
    if (empty($_SESSION['announcement_csrf'])) {
        $_SESSION['announcement_csrf'] = bin2hex(random_bytes(32));
    }

    if (isset($_POST['submit'])) {
        $title = trim($_POST['title']);
        $priority = trim($_POST['priority']);
        $message = trim($_POST['message']);
        $status = isset($_POST['status']) ? intval($_POST['status']) : 1;
        $created_by = !empty($_SESSION['adminname']) ? $_SESSION['adminname'] : 'Admin';

        if (!hash_equals($_SESSION['announcement_csrf'], $_POST['csrf_token'] ?? '')) {
            $errormsg = 'Your form session has expired. Please try again.';
        } elseif (empty($title) || empty($message)) {
            $errormsg = "Title and Announcement Message are required fields.";
        } elseif (!in_array($priority, array('General', 'Important', 'Urgent', 'Event'), true)) {
            $errormsg = 'Please select a valid priority level.';
        } else {
            $status = $status === 1 ? 1 : 0;
            try {
                $sql = "INSERT INTO tblannouncements (title, priority, message, status, created_by) 
                        VALUES (:title, :priority, :message, :status, :created_by)";
                $query = $dbh->prepare($sql);
                $query->bindParam(':title', $title, PDO::PARAM_STR);
                $query->bindParam(':priority', $priority, PDO::PARAM_STR);
                $query->bindParam(':message', $message, PDO::PARAM_STR);
                $query->bindParam(':status', $status, PDO::PARAM_INT);
                $query->bindParam(':created_by', $created_by, PDO::PARAM_STR);
                $query->execute();
                $lastInsertId = $dbh->lastInsertId();

                if ($lastInsertId > 0) {
                    $msg = $status === 1
                        ? 'Announcement posted successfully! Members can now view it.'
                        : 'Announcement saved as inactive.';
                } else {
                    $errormsg = "Failed to post announcement. Please try again.";
                }
            } catch (PDOException $e) {
                $errormsg = 'Unable to post the announcement. Please try again.';
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Admin | Add Announcement</title>
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

    <main class="app-content">
      <div class="app-title">
        <div>
          <h1><i class="fa fa-bullhorn"></i> Add Announcement</h1>
          <p>Broadcast notices, updates, and urgent alerts to all gym members</p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
          <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
          <li class="breadcrumb-item"><a href="manage-announcements.php">Announcements</a></li>
          <li class="breadcrumb-item active">Add</li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-10 mx-auto">
          <div class="tile">
            <h3 class="tile-title"><i class="fa fa-pencil-square-o"></i> Compose New Announcement</h3>
            <hr>

            <?php if(!empty($msg)): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle"></i> <strong>Success!</strong> <?php echo htmlentities($msg); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            <?php endif; ?>

            <?php if(!empty($errormsg)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle"></i> <strong>Error!</strong> <?php echo htmlentities($errormsg); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            <?php endif; ?>

            <div class="tile-body">
              <form method="post" action="add-announcement.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlentities($_SESSION['announcement_csrf']); ?>">
                <div class="form-group">
                  <label class="control-label font-weight-bold">Announcement Title <span class="text-danger">*</span></label>
                  <input class="form-control" type="text" name="title" placeholder="e.g. Scheduled Gym Maintenance on Saturday" required autofocus>
                </div>

                <div class="row">
                  <div class="col-md-6 form-group">
                    <label class="control-label font-weight-bold">Priority Level <span class="text-danger">*</span></label>
                    <select class="form-control" name="priority" required>
                      <option value="General">🔵 General Notice (Informational)</option>
                      <option value="Important">🟡 Important (High Priority)</option>
                      <option value="Urgent">🔴 Urgent (Critical Alert / Immediate Attention)</option>
                      <option value="Event">🟢 Event / Special Offer</option>
                    </select>
                  </div>

                  <div class="col-md-6 form-group">
                    <label class="control-label font-weight-bold">Publish Status <span class="text-danger">*</span></label>
                    <select class="form-control" name="status" required>
                      <option value="1">Active (Broadcast to all members immediately)</option>
                      <option value="0">Draft / Inactive (Do not publish yet)</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label class="control-label font-weight-bold">Message Content <span class="text-danger">*</span></label>
                  <textarea class="form-control" name="message" rows="6" placeholder="Type your detailed message to all members here..." required></textarea>
                </div>

                <div class="tile-footer">
                  <button class="btn btn-primary" type="submit" name="submit"><i class="fa fa-paper-plane"></i> Publish Announcement</button>
                  <a class="btn btn-secondary ml-2" href="manage-announcements.php"><i class="fa fa-arrow-left"></i> View All Announcements</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Essential javascripts for application to work-->
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/plugins/pace.min.js"></script>
  </body>
</html>
<?php } ?>
