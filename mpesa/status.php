<?php
// ============================================================
// M-Pesa payment status check (generic)
// Returns the current payment status for a payment_type + reference_id.
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
$payment_type = isset($_GET['payment_type']) ? trim($_GET['payment_type']) : '';
$reference_id = isset($_GET['reference_id']) ? intval($_GET['reference_id']) : 0;

if (!in_array($payment_type, array('package', 'class_series'))) {
    echo json_encode(array('success' => false, 'message' => 'Invalid payment type.'));
    exit;
}

if ($reference_id == 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid item.'));
    exit;
}

// Load the entitlement record (must belong to this user)
if ($payment_type == 'package') {
    $sql = "SELECT s.*, p.titlename AS item_title, p.Price AS price
            FROM tblsubscriptions s
            JOIN tbladdpackage p ON p.id = s.package_id
            WHERE s.id = :id AND s.user_id = :user_id";
} else {
    $sql = "SELECT e.*, cs.title AS item_title, cs.price
            FROM tblclass_enrollment e
            JOIN tblclass_series cs ON cs.id = e.series_id
            WHERE e.id = :id AND e.user_id = :user_id";
}
$stmt = $dbh->prepare($sql);
$stmt->bindParam(':id', $reference_id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$ent = $stmt->fetch(PDO::FETCH_OBJ);
if (!$ent) {
    echo json_encode(array('success' => false, 'message' => 'Item not found.'));
    exit;
}

// Load the latest payment row from the unified ledger
$psql = "SELECT * FROM tblpayments
         WHERE payment_type = :payment_type AND reference_id = :reference_id
         ORDER BY id DESC LIMIT 1";
$pstmt = $dbh->prepare($psql);
$pstmt->bindParam(':payment_type', $payment_type, PDO::PARAM_STR);
$pstmt->bindParam(':reference_id', $reference_id, PDO::PARAM_INT);
$pstmt->execute();
$pay = $pstmt->fetch(PDO::FETCH_OBJ);

$status = $ent->payment_status; // pending / paid / failed

// If still pending and we have a checkout request id, query Daraja as fallback
if ($status == 'pending' && $pay && $pay->checkout_request_id) {
    $mpesa = new Mpesa();
    $q = $mpesa->queryStatus($pay->checkout_request_id);
    if (isset($q['ResultCode'])) {
        if ($q['ResultCode'] == '0') {
            // Confirmed paid via query
            $upd = $dbh->prepare("UPDATE tblpayments
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

            if ($payment_type == 'package') {
                $upd2 = $dbh->prepare("UPDATE tblsubscriptions SET payment_status = 'paid', status = 'active' WHERE id = :id");
            } else {
                $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'paid' WHERE id = :id");
            }
            $upd2->bindParam(':id', $reference_id, PDO::PARAM_INT);
            $upd2->execute();
            $status = 'paid';
        } elseif (in_array($q['ResultCode'], array('1037', '1032'))) {
            // 1037 = timeout, 1032 = cancelled by user
            $st = ($q['ResultCode'] == '1037') ? 'TIMEOUT' : 'FAILED';
            $upd = $dbh->prepare("UPDATE tblpayments
                SET status = :st, result_code = :rc, result_desc = :rd WHERE id = :id");
            $upd->bindParam(':st', $st, PDO::PARAM_STR);
            $upd->bindParam(':rc', $q['ResultCode'], PDO::PARAM_STR);
            $upd->bindParam(':rd', $q['ResultDesc'], PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            if ($payment_type == 'package') {
                $del2 = $dbh->prepare("DELETE FROM tblsubscriptions WHERE id = :id");
            } else {
                $del2 = $dbh->prepare("DELETE FROM tblclass_enrollment WHERE id = :id");
            }
            $del2->bindParam(':id', $reference_id, PDO::PARAM_INT);
            $del2->execute();
            $status = 'failed';
        }
    }
}

echo json_encode(array(
    'success' => true,
    'status' => $status,
    'receipt' => ($pay && $pay->transaction_receipt) ? $pay->transaction_receipt : null,
    'amount' => $ent->price,
    'item_title' => $ent->item_title
));
?>
