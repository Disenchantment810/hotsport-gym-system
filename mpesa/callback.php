<?php
// ============================================================
// M-Pesa STK Push callback endpoint
// Safaricom POSTs the payment result here.
// ============================================================
error_reporting(0);
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Mpesa.php';

// Log the raw callback for debugging
$raw = file_get_contents('php://input');
if (!is_dir(__DIR__ . '/logs')) {
    @mkdir(__DIR__ . '/logs', 0775, true);
}
@file_put_contents(__DIR__ . '/logs/callback_' . date('Ymd_His') . '_' . uniqid() . '.json', $raw);

$mpesa = new Mpesa();
$cb = $mpesa->handleCallback($raw);

if ($cb['checkout_request_id']) {
    // Find the payment row by checkout request id
    $stmt = $dbh->prepare("SELECT * FROM tblclass_enrollment_payments WHERE checkout_request_id = :crid");
    $stmt->bindParam(':crid', $cb['checkout_request_id'], PDO::PARAM_STR);
    $stmt->execute();
    $pay = $stmt->fetch(PDO::FETCH_OBJ);

    if ($pay) {
        if ($cb['result_code'] == '0') {
            // Success
            $upd = $dbh->prepare("UPDATE tblclass_enrollment_payments
                SET status = 'SUCCESS', result_code = :rc, result_desc = :rd,
                    transaction_receipt = :receipt, transaction_date = :tdate
                WHERE id = :id");
            $upd->bindParam(':rc', $cb['result_code'], PDO::PARAM_STR);
            $upd->bindParam(':rd', $cb['result_desc'], PDO::PARAM_STR);
            $upd->bindParam(':receipt', $cb['receipt'], PDO::PARAM_STR);
            $upd->bindParam(':tdate', $cb['transaction_date'], PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            // Mark enrollment as paid
            $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'paid' WHERE id = :id");
            $upd2->bindParam(':id', $pay->enrollment_id, PDO::PARAM_INT);
            $upd2->execute();
        } else {
            // Failed / timeout
            $status = ($cb['result_code'] == '1037') ? 'TIMEOUT' : 'FAILED';
            $upd = $dbh->prepare("UPDATE tblclass_enrollment_payments
                SET status = :status, result_code = :rc, result_desc = :rd
                WHERE id = :id");
            $upd->bindParam(':status', $status, PDO::PARAM_STR);
            $upd->bindParam(':rc', $cb['result_code'], PDO::PARAM_STR);
            $upd->bindParam(':rd', $cb['result_desc'], PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'failed' WHERE id = :id");
            $upd2->bindParam(':id', $pay->enrollment_id, PDO::PARAM_INT);
            $upd2->execute();
        }
    }
}

// Always respond success so Safaricom stops retrying
header('Content-Type: application/json');
echo json_encode(array('ResultCode' => 0, 'ResultDesc' => 'Success'));
?>
