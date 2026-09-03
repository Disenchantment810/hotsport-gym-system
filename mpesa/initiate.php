<?php
// ============================================================
// M-Pesa payment initiation (generic)
// Supports payment_type = 'package' | 'class_series'
//   - package     : reference_id = package_id  -> creates a pending subscription
//   - class_series: reference_id = series_id   -> creates a pending enrollment
// Records a PENDING payment in tblpayments and triggers STK Push.
// Returns JSON.
// ============================================================
session_start();
error_reporting(0);
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Mpesa.php';

header('Content-Type: application/json');

if (strlen($_SESSION['uid']) == 0) {
    echo json_encode(array('success' => false, 'message' => 'Please log in first.'));
    exit;
}

$user_id = intval($_SESSION['uid']);
$payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : '';
$reference_id = isset($_POST['reference_id']) ? intval($_POST['reference_id']) : 0;
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

// --- Validate phone and normalize 07XXXXXXXX -> 2547XXXXXXXX ---
$phone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
    $phone = '254' . $phone;
} elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
    $phone = '254' . substr($phone, 1);
} elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
    // already normalized
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid phone number. Use format 07XXXXXXXX or 2547XXXXXXXX.'));
    exit;
}

if (!in_array($payment_type, array('package', 'class_series'))) {
    echo json_encode(array('success' => false, 'message' => 'Invalid payment type.'));
    exit;
}

if ($reference_id == 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid item.'));
    exit;
}

$amount = 0;
$title = '';
$ref_id = 0; // id of the entitlement record (subscription / enrollment)

if ($payment_type == 'package') {
    // --- Load package ---
    $sql = "SELECT * FROM tbladdpackage WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $reference_id, PDO::PARAM_INT);
    $stmt->execute();
    $pkg = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$pkg) {
        echo json_encode(array('success' => false, 'message' => 'Package not found.'));
        exit;
    }
    $amount = $pkg->Price;
    $title = $pkg->titlename;

    // --- Check for existing active/pending subscription (allow retry if failed) ---
    $chk = $dbh->prepare("SELECT id FROM tblsubscriptions
        WHERE package_id = :package_id AND user_id = :user_id
          AND (status = 'active' OR payment_status = 'pending')");
    $chk->bindParam(':package_id', $reference_id, PDO::PARAM_INT);
    $chk->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $chk->execute();
    if ($chk->rowCount() > 0) {
        echo json_encode(array('success' => false, 'message' => 'You already have an active or pending subscription for this package.'));
        exit;
    }

    // --- Create pending subscription (reuse a failed row if present) ---
    $chk2 = $dbh->prepare("SELECT id FROM tblsubscriptions
        WHERE package_id = :package_id AND user_id = :user_id
          AND payment_status = 'failed' ORDER BY id DESC LIMIT 1");
    $chk2->bindParam(':package_id', $reference_id, PDO::PARAM_INT);
    $chk2->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $chk2->execute();
    $failed = $chk2->fetch(PDO::FETCH_OBJ);
    if ($failed) {
        $upd = $dbh->prepare("UPDATE tblsubscriptions SET status = 'pending', payment_status = 'pending' WHERE id = :id");
        $upd->bindParam(':id', $failed->id, PDO::PARAM_INT);
        $upd->execute();
        $ref_id = $failed->id;
    } else {
        $ins = $dbh->prepare("INSERT INTO tblsubscriptions (package_id, user_id, status, payment_status)
            VALUES (:package_id, :user_id, 'pending', 'pending')");
        $ins->bindParam(':package_id', $reference_id, PDO::PARAM_INT);
        $ins->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $ins->execute();
        $ref_id = $dbh->lastInsertId();
    }
} else {
    // --- class_series: load series ---
    $sql = "SELECT * FROM tblclass_series WHERE id = :id AND status = 1";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':id', $reference_id, PDO::PARAM_INT);
    $stmt->execute();
    $series = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$series) {
        echo json_encode(array('success' => false, 'message' => 'Class series not found or inactive.'));
        exit;
    }
    $amount = $series->price;
    $title = $series->title;

    // --- Check duplicate enrollment (allow retry if failed) ---
    $chk = $dbh->prepare("SELECT id FROM tblclass_enrollment WHERE series_id = :series_id AND user_id = :user_id AND payment_status IN ('pending','paid')");
    $chk->bindParam(':series_id', $reference_id, PDO::PARAM_INT);
    $chk->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $chk->execute();
    if ($chk->rowCount() > 0) {
        echo json_encode(array('success' => false, 'message' => 'You have already enrolled in this class series.'));
        exit;
    }

    // --- Check capacity (on-demand count) ---
    $cnt = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_enrollment WHERE series_id = :series_id");
    $cnt->bindParam(':series_id', $reference_id, PDO::PARAM_INT);
    $cnt->execute();
    $enrolled = $cnt->fetch(PDO::FETCH_OBJ)->c;
    if ($enrolled >= $series->capacity) {
        echo json_encode(array('success' => false, 'message' => 'This class series is full.'));
        exit;
    }

    // --- Create pending enrollment (reuse a failed row if present) ---
    $chk2 = $dbh->prepare("SELECT id FROM tblclass_enrollment WHERE series_id = :series_id AND user_id = :user_id AND payment_status = 'failed' ORDER BY id DESC LIMIT 1");
    $chk2->bindParam(':series_id', $reference_id, PDO::PARAM_INT);
    $chk2->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $chk2->execute();
    $failed = $chk2->fetch(PDO::FETCH_OBJ);
    if ($failed) {
        $upd = $dbh->prepare("UPDATE tblclass_enrollment SET status = 'enrolled', payment_status = 'pending' WHERE id = :id");
        $upd->bindParam(':id', $failed->id, PDO::PARAM_INT);
        $upd->execute();
        $ref_id = $failed->id;
    } else {
        $ins = $dbh->prepare("INSERT INTO tblclass_enrollment (series_id, user_id, status, payment_status)
            VALUES (:series_id, :user_id, 'enrolled', 'pending')");
        $ins->bindParam(':series_id', $reference_id, PDO::PARAM_INT);
        $ins->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $ins->execute();
        $ref_id = $dbh->lastInsertId();
    }
}

// --- Initiate STK Push ---
$mpesa = new Mpesa();
$accountRef = 'HSP-' . strtoupper($payment_type) . '-' . $ref_id;
$result = $mpesa->stkPush($phone, $amount, $accountRef, ucfirst(str_replace('_', ' ', $payment_type)) . ': ' . $title);

if (isset($result['error'])) {
    // Roll back the pending entitlement record on STK failure
    if ($payment_type == 'package') {
        $del = $dbh->prepare("DELETE FROM tblsubscriptions WHERE id = :id");
    } else {
        $del = $dbh->prepare("DELETE FROM tblclass_enrollment WHERE id = :id");
    }
    $del->bindParam(':id', $ref_id, PDO::PARAM_INT);
    $del->execute();
    echo json_encode(array('success' => false, 'message' => 'Could not initiate payment: ' . $result['error']));
    exit;
}

// --- Record the PENDING payment row in the unified ledger ---
$crid = isset($result['CheckoutRequestID']) ? $result['CheckoutRequestID'] : null;
$mrid = isset($result['MerchantRequestID']) ? $result['MerchantRequestID'] : null;
$pay = $dbh->prepare("INSERT INTO tblpayments
    (user_id, payment_type, reference_id, amount, phone_number, checkout_request_id, merchant_request_id, status)
    VALUES (:user_id, :payment_type, :reference_id, :amount, :phone, :crid, :mrid, 'PENDING')");
$pay->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$pay->bindParam(':payment_type', $payment_type, PDO::PARAM_STR);
$pay->bindParam(':reference_id', $ref_id, PDO::PARAM_INT);
$pay->bindParam(':amount', $amount, PDO::PARAM_STR);
$pay->bindParam(':phone', $phone, PDO::PARAM_STR);
$pay->bindParam(':crid', $crid, PDO::PARAM_STR);
$pay->bindParam(':mrid', $mrid, PDO::PARAM_STR);
$pay->execute();
$payment_id = $dbh->lastInsertId();

echo json_encode(array(
    'success' => true,
    'message' => 'Payment initiated. Check your phone and enter your M-Pesa PIN.',
    'checkout_request_id' => $crid,
    'payment_id' => $payment_id,
    'reference_id' => $ref_id,
    'payment_type' => $payment_type,
    'amount' => $amount
));
?>
