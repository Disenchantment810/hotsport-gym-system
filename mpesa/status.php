<?php
// ============================================================
// M-Pesa payment status check
// Returns the current payment status for an enrollment.
// If the callback hasn't arrived yet, falls back to the
// Daraja STK Query endpoint.
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
$enrollment_id = isset($_GET['enrollment_id']) ? intval($_GET['enrollment_id']) : 0;

if ($enrollment_id == 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid enrollment.'));
    exit;
}

// Load the enrollment (must belong to this user)
$sql = "SELECT e.*, cs.title AS series_title, cs.price
        FROM tblclass_enrollment e
        JOIN tblclass_series cs ON cs.id = e.series_id
        WHERE e.id = :id AND e.user_id = :user_id";
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':id', $enrollment_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$enroll = $stmt->fetch(PDO::FETCH_OBJ);
if (!$enroll) {
    echo json_encode(array('success' => false, 'message' => 'Enrollment not found.'));
    exit;
}

// Load the payment row
$psql = "SELECT * FROM tblclass_enrollment_payments WHERE enrollment_id = :enrollment_id ORDER BY id DESC LIMIT 1";
$pstmt = $dbh->prepare($psql);
$pstmt->bindParam(':enrollment_id', $enrollment_id, PDO::PARAM_INT);
$pstmt->execute();
$pay = $pstmt->fetch(PDO::FETCH_OBJ);

$status = $enroll->payment_status; // pending / paid / failed

// If still pending and we have a checkout request id, query Daraja as fallback
if ($status == 'pending' && $pay && $pay->checkout_request_id) {
    $mpesa = new Mpesa();
    $q = $mpesa->queryStatus($pay->checkout_request_id);
    if (isset($q['ResultCode'])) {
        if ($q['ResultCode'] == '0') {
            // Confirmed paid via query
            $upd = $dbh->prepare("UPDATE tblclass_enrollment_payments
                SET status = 'SUCCESS', result_code = :rc, result_desc = :rd,
                    transaction_receipt = :receipt
                WHERE id = :id");
            $rc = '0';
            $rd = isset($q['ResultDesc']) ? $q['ResultDesc'] : 'Success';
            $receipt = isset($q['MpesaReceiptNumber']) ? $q['MpesaReceiptNumber'] : null;
            $upd->bindParam(':rc', $rc, PDO::PARAM_STR);
            $upd->bindParam(':rd', $rd, PDO::PARAM_STR);
            $upd->bindParam(':receipt', $receipt, PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'paid' WHERE id = :id");
            $upd2->bindParam(':id', $enrollment_id, PDO::PARAM_INT);
            $upd2->execute();
            $status = 'paid';
        } elseif (in_array($q['ResultCode'], array('1037', '1032'))) {
            // 1037 = timeout, 1032 = cancelled by user
            $st = ($q['ResultCode'] == '1037') ? 'TIMEOUT' : 'FAILED';
            $upd = $dbh->prepare("UPDATE tblclass_enrollment_payments
                SET status = :st, result_code = :rc, result_desc = :rd WHERE id = :id");
            $upd->bindParam(':st', $st, PDO::PARAM_STR);
            $upd->bindParam(':rc', $q['ResultCode'], PDO::PARAM_STR);
            $upd->bindParam(':rd', $q['ResultDesc'], PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'failed' WHERE id = :id");
            $upd2->bindParam(':id', $enrollment_id, PDO::PARAM_INT);
            $upd2->execute();
            $status = 'failed';
        }
    }
}

echo json_encode(array(
    'success' => true,
    'status' => $status,
    'receipt' => ($pay && $pay->transaction_receipt) ? $pay->transaction_receipt : null,
    'amount' => $enroll->price,
    'series_title' => $enroll->series_title
));
?>
