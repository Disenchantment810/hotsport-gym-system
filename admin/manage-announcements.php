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

    // Handle administrative changes using POST so a link preview cannot change data.
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        if (!hash_equals($_SESSION['announcement_csrf'], $_POST['csrf_token'] ?? '')) {
            $errormsg = 'Your form session has expired. Please try again.';
        } else {
        try {
            $sql = "DELETE FROM tblannouncements WHERE id = :id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $msg = "Announcement deleted successfully.";
        } catch (PDOException $e) {
            $errormsg = 'Unable to delete the announcement. Please try again.';
        }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'toggle' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $new_status = isset($_POST['status']) && $_POST['status'] == 1 ? 0 : 1;
        if (!hash_equals($_SESSION['announcement_csrf'], $_POST['csrf_token'] ?? '')) {
            $errormsg = 'Your form session has expired. Please try again.';
        } else {
        try {
            $sql = "UPDATE tblannouncements SET status = :status WHERE id = :id";
            $query = $dbh->prepare($sql);
            $query->bindParam(':status', $new_status, PDO::PARAM_INT);
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            $msg = "Announcement status updated.";
        } catch (PDOException $e) {
            $errormsg = 'Unable to update the announcement. Please try again.';
        }
        }
    }

    // Handle Read Receipts AJAX request
    if (isset($_POST['action']) && $_POST['action'] == 'get_readers') {
        header('Content-Type: application/json');
        if (!hash_equals($_SESSION['announcement_csrf'], $_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(array('success' => false));
            exit;
        }
        $announcement_id = intval($_POST['announcement_id']);
        $sql = "SELECT u.fname, u.lname, u.email, u.mobile, ar.read_at 
                FROM tblannouncement_reads ar
                INNER JOIN tbluser u ON ar.user_id = u.id
                WHERE ar.announcement_id = :aid
                ORDER BY ar.read_at DESC";
        $query = $dbh->prepare($sql);
        $query->bindParam(':aid', $announcement_id, PDO::PARAM_INT);
        $query->execute();
        $readers = $query->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(array('success' => true, 'readers' => $readers));
        exit;
    }

    // Fetch all announcements
    $sql = "SELECT a.*, 
                   (SELECT COUNT(*) FROM tblannouncement_reads ar WHERE ar.announcement_id = a.id) as read_count,
                   (SELECT COUNT(*) FROM tbluser) as total_users
            FROM tblannouncements a 
            ORDER BY a.created_at DESC";
    $query = $dbh->prepare($sql);
    $query->execute();
    $announcements = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Admin | Manage Announcements</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
      .badge-urgent { background-color: #dc3545; color: #fff; }
      .badge-important { background-color: #fd7e14; color: #fff; }
      .badge-general { background-color: #17a2b8; color: #fff; }
      .badge-event { background-color: #28a745; color: #fff; }
    </style>
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
          <h1><i class="fa fa-bullhorn"></i> Manage Announcements</h1>
          <p>View, edit, archive announcements, and track member read receipts</p>
        </div>
        <ul class="app-breadcrumb breadcrumb">
          <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
          <li class="breadcrumb-item"><a href="manage-announcements.php">Announcements</a></li>
          <li class="breadcrumb-item active">Manage</li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="tile-title mb-0"><i class="fa fa-list"></i> Posted Announcements</h3>
              <a href="add-announcement.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add New Announcement</a>
            </div>
            <hr>

            <?php if(!empty($msg)): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle"></i> <?php echo htmlentities($msg); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            <?php endif; ?>

            <?php if(!empty($errormsg)): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-triangle"></i> <?php echo htmlentities($errormsg); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
            <?php endif; ?>

            <div class="tile-body table-responsive">
              <table class="table table-hover table-bordered" id="sampleTable">
                <thead>
                  <tr>
                    <th style="width: 50px;">#</th>
                    <th>Title & Preview</th>
                    <th style="width: 100px;">Priority</th>
                    <th style="width: 140px;">Posted Date</th>
                    <th style="width: 130px;">Read Status</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 150px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  $cnt = 1;
                  foreach ($announcements as $row): 
                    $priority = $row['priority'];
                    $badgeClass = 'badge-general';
                    if (strcasecmp($priority, 'Urgent') == 0) $badgeClass = 'badge-urgent';
                    else if (strcasecmp($priority, 'Important') == 0) $badgeClass = 'badge-important';
                    else if (strcasecmp($priority, 'Event') == 0) $badgeClass = 'badge-event';

                    $read_count = intval($row['read_count']);
                    $total_users = intval($row['total_users']);
                    $pct = $total_users > 0 ? round(($read_count / $total_users) * 100) : 0;
                  ?>
                  <tr>
                    <td><?php echo $cnt; ?></td>
                    <td>
                      <strong class="d-block text-dark" style="font-size: 15px;"><?php echo htmlentities($row['title']); ?></strong>
                      <small class="text-muted d-block mt-1" style="max-width: 420px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlentities(substr($row['message'], 0, 120)) . (strlen($row['message']) > 120 ? '...' : ''); ?>
                      </small>
                    </td>
                    <td>
                      <span class="badge <?php echo $badgeClass; ?> p-2 font-weight-bold"><?php echo htmlentities($priority); ?></span>
                    </td>
                    <td>
                      <small><i class="fa fa-calendar"></i> <?php echo date('M d, Y', strtotime($row['created_at'])); ?></small><br>
                      <small class="text-muted"><i class="fa fa-clock-o"></i> <?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                    </td>
                    <td>
                      <button type="button" class="btn btn-outline-info btn-sm view-readers-btn" data-id="<?php echo $row['id']; ?>" data-title="<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>">
                        <i class="fa fa-eye"></i> <?php echo "$read_count / $total_users"; ?> (<?php echo $pct; ?>%)
                      </button>
                    </td>
                    <td>
                      <?php if ($row['status'] == 1): ?>
                        <span class="badge badge-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Archived</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="edit-announcement.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit Announcement">
                        <i class="fa fa-edit"></i>
                      </a>
                      
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlentities($_SESSION['announcement_csrf']); ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="status" value="<?php echo $row['status']; ?>">
                        <button type="submit" class="btn btn-sm <?php echo $row['status'] == 1 ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo $row['status'] == 1 ? 'Archive / Deactivate' : 'Activate'; ?>">
                        <i class="fa <?php echo $row['status'] == 1 ? 'fa-pause' : 'fa-play'; ?>"></i>
                        </button>
                      </form>

                      <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this announcement?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlentities($_SESSION['announcement_csrf']); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" title="Delete Announcement"><i class="fa fa-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                  <?php $cnt++; endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Read Receipts Modal -->
    <div class="modal fade" id="readersModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="readersModalTitle"><i class="fa fa-users"></i> Member Read Receipts</h5>
            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div id="readers-loading" class="text-center py-4">
              <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
              <p class="mt-2 text-muted">Loading read receipts...</p>
            </div>
            <div id="readers-content" style="display: none;">
              <table class="table table-bordered table-striped" id="readersTable">
                <thead>
                  <tr>
                    <th>Member Name</th>
                    <th>Email Address</th>
                    <th>Mobile</th>
                    <th>Read At</th>
                  </tr>
                </thead>
                <tbody id="readersTableBody"></tbody>
              </table>
            </div>
            <div id="readers-empty" style="display: none;" class="text-center py-4 text-muted">
              <i class="fa fa-info-circle fa-2x mb-2 text-warning"></i>
              <p>No members have marked this announcement as read yet.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Essential javascripts -->
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/plugins/pace.min.js"></script>
    <script type="text/javascript" src="js/plugins/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="js/plugins/dataTables.bootstrap.min.js"></script>
    <script type="text/javascript">
      $(document).ready(function() {
        $('#sampleTable').DataTable({
          "order": [[ 0, "asc" ]]
        });

        $('.view-readers-btn').on('click', function() {
          var aid = $(this).data('id');
          var title = $(this).data('title');
          $('#readersModalTitle').html('<i class="fa fa-users"></i> Read Receipts: ' + $('<div>').text(title).html());
          $('#readers-loading').show();
          $('#readers-content').hide();
          $('#readers-empty').hide();
          $('#readersModal').modal('show');

          $.ajax({
            url: 'manage-announcements.php',
            method: 'POST',
            data: {
              action: 'get_readers',
              announcement_id: aid,
              csrf_token: '<?php echo $_SESSION['announcement_csrf']; ?>'
            },
            dataType: 'json',
            success: function(res) {
              $('#readers-loading').hide();
              if (res.success && res.readers && res.readers.length > 0) {
                var tbody = $('#readersTableBody');
                tbody.empty();
                res.readers.forEach(function(r) {
                  var row = '<tr>' +
                    '<td><strong>' + $('<div>').text(r.fname + ' ' + r.lname).html() + '</strong></td>' +
                    '<td>' + $('<div>').text(r.email).html() + '</td>' +
                    '<td>' + $('<div>').text(r.mobile || '-').html() + '</td>' +
                    '<td><span class="badge badge-success"><i class="fa fa-check"></i> ' + $('<div>').text(r.read_at).html() + '</span></td>' +
                    '</tr>';
                  tbody.append(row);
                });
                $('#readers-content').show();
              } else {
                $('#readers-empty').show();
              }
            },
            error: function() {
              $('#readers-loading').hide();
              $('#readers-empty').html('<p class="text-danger">Failed to load read receipts.</p>').show();
            }
          });
        });
      });
    </script>
  </body>
</html>
<?php } ?>
