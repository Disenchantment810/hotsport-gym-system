<?php
session_start();
error_reporting(0);
require_once('include/config.php');
require_once('../include/csrf.php');
require_once('../include/login_throttle.php');
require_once('../include/password_migration.php');
$msg = "";
if(isset($_POST['submit'])) {
  if (!csrf_verify()) {
    $msg = "Invalid request. Please try again.";
  } else {
  $email = trim($_POST['email']);
  if (throttle_blocked($dbh, $email)) {
    $msg = "Too many failed attempts. Please try again later.";
  } else {
  $plainPassword = isset($_POST['password']) ? $_POST['password'] : '';
  if($email != "" && $plainPassword != "") {
    try {
      $query = "select id, name, email, mobile, password, status from tbltrainers where email=:email";
      $stmt = $dbh->prepare($query);
      $stmt->bindParam('email', $email, PDO::PARAM_STR);
      $stmt->execute();
      $row   = $stmt->fetch(PDO::FETCH_ASSOC);
      if(!empty($row) && !empty($row['password']) && password_verify_compat($plainPassword, $row['password'])) {
        if($row['status'] == 0) {
          $msg = "Your account is inactive. Contact the administrator.";
        } else {
          // Transparently upgrade a legacy MD5 hash to password_hash().
          if (password_needs_upgrade($row['password'])) {
            $rehash = $dbh->prepare("update tbltrainers set password=:newpassword where id=:id");
            $newhash = password_hash_new($plainPassword);
            $rehash->bindParam(':newpassword', $newhash, PDO::PARAM_STR);
            $rehash->bindParam(':id', $row['id'], PDO::PARAM_INT);
            $rehash->execute();
          }
          throttle_clear($dbh, $email);
          session_regenerate_id(true);
          $_SESSION['trainerid']   = $row['id'];
          $_SESSION['email'] = $row['email'];
          $_SESSION['name'] = $row['name'];
          header("location: index.php");
        }
      } else {
        throttle_fail($dbh, $email);
        $msg = "Invalid username and password!";
      }
    } catch (PDOException $e) {
      echo "Error : ".$e->getMessage();
    }
  } else {
    $msg = "Both fields are required!";
  }
  }
  }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="../admin/css/main.css">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>HOTSPORT | Trainer login</title>
  </head>
  <body>
      <marquee onMouseOver="this.stop()" style="color: #e92f33;" onMouseOut="this.start()">Welcome to HOTSPORT GYM SYSTEM | Trainer Portal</marquee>

    <section class="material-half-bg">
      <div class="cover"></div>
    </section>
    <section class="login-content">
      <div class="logo">
        <h1>HOTSPORT | Trainer login</h1>
      </div>
      <div class="login-box">
        <form class="login-form" method="post">
          <?php csrf_field(); ?>
          <h3 class="login-head"><i class="fa fa-lg fa-fw fa-user"></i>SIGN IN</h3>
           <?php if($msg){?><div class="succWrap" style="color:red;"><strong>Error</strong>:<?php echo htmlentities($msg); ?> </div><?php }?>
          <div class="form-group">
            <label class="control-label">Email</label>
            <input class="form-control" name="email" id="email" type="text" placeholder="Email" autofocus>
          </div>
          <div class="form-group">
            <label class="control-label">PASSWORD</label>
            <input class="form-control" name="password" id="password" type="password" placeholder="Password">
          </div>
          <div class="form-group btn-container">
            <input type="submit" name="submit" id="submit" value="SIGN IN" class="btn btn-primary btn-block">
          </div>
          <hr />
          <a href="../index.php">Back to Home Page</a>
        </form>
      </div>
    </section>
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
