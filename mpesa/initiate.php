<?php
// ============================================================
// M-Pesa enrollment initiation
// Receives series_id + phone, creates pending enrollment,
// records a PENDING payment, and triggers STK Push.
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
$series_id = isset($_POST['series_id']) ? intval($_POST['series_id']) : 0;
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

if ($series_id == 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid class series.'));
    exit;
}

// --- Load series ---
$sql = "SELECT * FROM tblclass_series WHERE id = :id AND status = 1";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':id', $series_id, PDO::PARAM_INT);
$stmt->execute();
$series = $stmt->fetch(PDO::FETCH_OBJ);
if (!$series) {
    echo json_encode(array('success' => false, 'message' => 'Class series not found or inactive.'));
    exit;
}

// --- Check duplicate enrollment ---
$chk = $dbh->prepare("SELECT id FROM tblclass_enrollment WHERE series_id = :series_id AND user_id = :user_id");
$chk->bindParam(':series_id', $series_id, PDO::PARAM_INT);
$chk->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$chk->execute();
if ($chk->rowCount() > 0) {
    echo json_encode(array('success' => false, 'message' => 'You have already enrolled in this class series.'));
    exit;
}

// --- Check capacity (on-demand count) ---
$cnt = $dbh->prepare("SELECT COUNT(*) AS c FROM tblclass_enrollment WHERE series_id = :series_id");
$cnt->bindParam(':series_id', $series_id, PDO::PARAM_INT);
$cnt->execute();
$enrolled = $cnt->fetch(PDO::FETCH_OBJ)->c;
if ($enrolled >= $series->capacity) {
    echo json_encode(array('success' => false, 'message' => 'This class series is full.'));
    exit;
}

// --- Create pending enrollment ---
$ins = $dbh->prepare("INSERT INTO tblclass_enrollment (series_id, user_id, status, payment_status) VALUES (:series_id, :user_id, 'enrolled', 'pending')");
$ins->bindParam(':series_id', $series_id, PDO::PARAM_INT);
$ins->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$ins->execute();
$enrollment_id = $dbh->lastInsertId();

// --- Initiate STK Push ---
$mpesa = new Mpesa();
$accountRef = 'HSP-' . $series_id . '-' . $enrollment_id;
$result = $mpesa->stkPush($phone, $series->price, $accountRef, 'Class enrollment: ' . $series->title);

if (isset($result['error'])) {
    // Roll back the pending enrollment on STK failure
    $del = $dbh->prepare("DELETE FROM tblclass_enrollment WHERE id = :id");
    $del->bindParam(':id', $enrollment_id, PDO::PARAM_INT);
    $del->execute();
    echo json_encode(array('success' => false, 'message' => 'Could not initiate payment: ' . $result['error']));
    exit;
}

// --- Record the PENDING payment row ---
$crid = isset($result['CheckoutRequestID']) ? $result['CheckoutRequestID'] : null;
$mrid = isset($result['MerchantRequestID']) ? $result['MerchantRequestID'] : null;
$pay = $dbh->prepare("INSERT INTO tblclass_enrollment_payments
    (enrollment_id, checkout_request_id, merchant_request_id, phone_number, amount, status)
    VALUES (:enrollment_id, :crid, :mrid, :phone, :amount, 'PENDING')");
$pay->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
$pay->bindParam(':crid', $crid, PDO::PARAM_STR);
$pay->bindParam(':mrid', $mrid, PDO::PARAM_STR);
$pay->bindParam(':phone', $phone, PDO::PARAM_STR);
$pay->bindParam(':amount', $series->price, PDO::PARAM_STR);
$pay->execute();

echo json_encode(array(
    'success' => true,
    'message' => 'Payment initiated. Check your phone and enter your M-Pesa PIN.',
    'checkout_request_id' => $crid,
    'enrollment_id' => $enrollment_id,
    'amount' => $series->price
));
?>
