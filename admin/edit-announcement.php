<?php
session_start();
error_reporting(0);
require_once('include/config.php');

if (strlen($_SESSION['adminid']) == 0) {
    header('location:logout.php');
    exit;
}

if (empty($_SESSION['announcement_csrf'])) {
    $_SESSION['announcement_csrf'] = bin2hex(random_bytes(32));
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1) {
    header('location:manage-announcements.php');
    exit;
}

$msg = '';
$errormsg = '';
if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $status = isset($_POST['status']) && $_POST['status'] == 1 ? 1 : 0;

    if (!hash_equals($_SESSION['announcement_csrf'], $_POST['csrf_token'] ?? '')) {
        $errormsg = 'Your form session has expired. Please try again.';
    } elseif ($title === '' || $message === '') {
        $errormsg = 'Title and announcement message are required fields.';
    } elseif (!in_array($priority, array('General', 'Important', 'Urgent', 'Event'), true)) {
        $errormsg = 'Please select a valid priority level.';
    } else {
        try {
            $sql = 'UPDATE tblannouncements SET title = :title, priority = :priority, message = :message, status = :status WHERE id = :id';
            $query = $dbh->prepare($sql);
            $query->execute(array(':title' => $title, ':priority' => $priority, ':message' => $message, ':status' => $status, ':id' => $id));
            $msg = 'Announcement updated successfully.';
        } catch (PDOException $e) {
            $errormsg = 'Unable to update the announcement. Please try again.';
        }
    }
}

$query = $dbh->prepare('SELECT * FROM tblannouncements WHERE id = :id');
$query->execute(array(':id' => $id));
$announcement = $query->fetch(PDO::FETCH_ASSOC);
if (!$announcement) {
    header('location:manage-announcements.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Admin | Edit Announcement</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="css/main.css">
  <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="app sidebar-mini rtl">
  <?php include 'include/header.php'; ?>
  <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
  <?php include 'include/sidebar.php'; ?>
  <main class="app-content">
    <div class="app-title"><div><h1><i class="fa fa-bullhorn"></i> Edit Announcement</h1></div></div>
    <div class="row"><div class="col-md-10 mx-auto"><div class="tile">
      <h3 class="tile-title">Update Announcement</h3><hr>
      <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlentities($msg); ?></div><?php endif; ?>
      <?php if ($errormsg): ?><div class="alert alert-danger"><?php echo htmlentities($errormsg); ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlentities($_SESSION['announcement_csrf']); ?>">
        <div class="form-group"><label class="control-label font-weight-bold">Announcement Title</label><input class="form-control" type="text" name="title" maxlength="255" required value="<?php echo htmlentities($announcement['title']); ?>"></div>
        <div class="row">
          <div class="col-md-6 form-group"><label class="control-label font-weight-bold">Priority Level</label><select class="form-control" name="priority" required><?php foreach (array('General', 'Important', 'Urgent', 'Event') as $priority): ?><option value="<?php echo $priority; ?>"<?php echo $announcement['priority'] === $priority ? ' selected' : ''; ?>><?php echo $priority; ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6 form-group"><label class="control-label font-weight-bold">Publish Status</label><select class="form-control" name="status"><option value="1"<?php echo $announcement['status'] == 1 ? ' selected' : ''; ?>>Active</option><option value="0"<?php echo $announcement['status'] == 0 ? ' selected' : ''; ?>>Draft / Inactive</option></select></div>
        </div>
        <div class="form-group"><label class="control-label font-weight-bold">Message Content</label><textarea class="form-control" name="message" rows="6" required><?php echo htmlentities($announcement['message']); ?></textarea></div>
        <div class="tile-footer"><button class="btn btn-primary" type="submit" name="submit"><i class="fa fa-save"></i> Save Changes</button> <a class="btn btn-secondary" href="manage-announcements.php">Cancel</a></div>
      </form>
    </div></div></div>
  </main>
  <script src="js/jquery-3.2.1.min.js"></script><script src="js/popper.min.js"></script><script src="js/bootstrap.min.js"></script><script src="js/main.js"></script>
</body>
</html>
