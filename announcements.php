<?php
session_start();
error_reporting(0);
require_once('include/config.php');

if (strlen($_SESSION['uid']) == 0) {
    header('location:login.php');
    exit;
}

$uid = intval($_SESSION['uid']);
try {
    // Recording a receipt is idempotent because the table has a unique announcement/user key.
    $markRead = $dbh->prepare('INSERT IGNORE INTO tblannouncement_reads (announcement_id, user_id) SELECT id, :uid FROM tblannouncements WHERE status = 1');
    $markRead->execute(array(':uid' => $uid));
    $query = $dbh->prepare('SELECT title, message, priority, created_at FROM tblannouncements WHERE status = 1 ORDER BY created_at DESC');
    $query->execute();
    $announcements = $query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $announcements = array();
    $loadError = 'Announcements are temporarily unavailable. Please try again later.';
}

function announcementClass($priority) {
    $classes = array('Urgent' => 'danger', 'Important' => 'warning', 'Event' => 'success', 'General' => 'info');
    return isset($classes[$priority]) ? $classes[$priority] : 'info';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>HOTSPORT | Announcements</title>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/bootstrap.min.css"><link rel="stylesheet" href="css/font-awesome.min.css"><link rel="stylesheet" href="css/owl.carousel.min.css"><link rel="stylesheet" href="css/nice-select.css"><link rel="stylesheet" href="css/slicknav.min.css"><link rel="stylesheet" href="css/style.css">
</head>
<body>
  <?php include 'include/header.php'; ?>
  <section class="page-top-section set-bg" data-setbg="img/page-top-bg.jpg"><div class="container"><div class="row"><div class="col-lg-7 m-auto text-white"><h2>Announcements</h2><p>Gym updates, events, and important notices</p></div></div></div></section>
  <section class="contact-page-section spad overflow-hidden"><div class="container"><div class="row"><div class="col-lg-9 mx-auto">
    <?php if (!empty($loadError)): ?><div class="alert alert-danger"><?php echo htmlentities($loadError); ?></div><?php elseif (!$announcements): ?><div class="alert alert-info">There are no announcements at the moment.</div><?php else: foreach ($announcements as $announcement): ?>
      <div class="card mb-4 shadow-sm"><div class="card-body"><span class="badge badge-<?php echo announcementClass($announcement['priority']); ?> float-right"><?php echo htmlentities($announcement['priority']); ?></span><h4 class="card-title pr-5"><?php echo htmlentities($announcement['title']); ?></h4><small class="text-muted"><i class="fa fa-calendar"></i> <?php echo date('M d, Y h:i A', strtotime($announcement['created_at'])); ?></small><hr><p class="card-text" style="white-space: pre-line;"><?php echo htmlentities($announcement['message']); ?></p></div></div>
    <?php endforeach; endif; ?>
  </div></div></div></section>
  <?php include 'include/footer.php'; ?>
  <script src="js/vendor/jquery-3.2.1.min.js"></script><script src="js/popper.min.js"></script><script src="js/bootstrap.min.js"></script><script src="js/main.js"></script>
</body>
</html>
