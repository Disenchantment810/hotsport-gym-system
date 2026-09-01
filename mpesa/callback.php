<?php
// ============================================================
// M-Pesa STK Push callback endpoint (generic)
// Safaricom POSTs the payment result here.
// Matches checkout_request_id in tblpayments and updates the
// linked entitlement record by payment_type.
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

// Parse a free-text duration like "3 Month", "2 Weeks", "1 Year" into a DateInterval
function mpesa_parse_duration($duration) {
    if (preg_match('/(\d+)\s*(day|week|month|year)s?/i', trim($duration), $m)) {
        $n = (int)$m[1];
        $unit = strtolower($m[2]);
        switch ($unit) {
            case 'day':   return new DateInterval('P' . $n . 'D');
            case 'week':  return new DateInterval('P' . $n . 'W');
            case 'month': return new DateInterval('P' . $n . 'M');
            case 'year':  return new DateInterval('P' . $n . 'Y');
        }
    }
    return null;
}

// Convert Daraja timestamp (YYYYMMDDHHMMSS) to a MySQL datetime
function mpesa_parse_txdate($ts) {
    if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', trim($ts), $m)) {
        return $m[1] . '-' . $m[2] . '-' . $m[3] . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6];
    }
    return null;
}

$mpesa = new Mpesa();
$cb = $mpesa->handleCallback($raw);

if ($cb['checkout_request_id']) {
    // Find the payment row by checkout request id
    $stmt = $dbh->prepare("SELECT * FROM tblpayments WHERE checkout_request_id = :crid");
    $stmt->bindParam(':crid', $cb['checkout_request_id'], PDO::PARAM_STR);
    $stmt->execute();
    $pay = $stmt->fetch(PDO::FETCH_OBJ);

    if ($pay) {
        if ($cb['result_code'] == '0') {
            // Success
            $upd = $dbh->prepare("UPDATE tblpayments
                SET status = 'SUCCESS', result_code = :rc, result_desc = :rd,
                    transaction_receipt = :receipt, transaction_date = :tdate
                WHERE id = :id");
            $upd->bindParam(':rc', $cb['result_code'], PDO::PARAM_STR);
            $upd->bindParam(':rd', $cb['result_desc'], PDO::PARAM_STR);
            $upd->bindParam(':receipt', $cb['receipt'], PDO::PARAM_STR);
            $txdate = mpesa_parse_txdate($cb['transaction_date']);
            $upd->bindParam(':tdate', $txdate, PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            // Mark the linked entitlement as paid
            if ($pay->payment_type == 'package') {
                $pkg = $dbh->prepare("SELECT PackageDuratiobn FROM tbladdpackage WHERE id = :id");
                $pkg->bindParam(':id', $pay->reference_id, PDO::PARAM_INT);
                $pkg->execute();
                $prow = $pkg->fetch(PDO::FETCH_OBJ);

                $start = date('Y-m-d');
                $end = null;
                $di = $prow ? mpesa_parse_duration($prow->PackageDuratiobn) : null;
                if ($di) {
                    $dt = new DateTime($start);
                    $dt->add($di);
                    $end = $dt->format('Y-m-d');
                }

                $upd2 = $dbh->prepare("UPDATE tblsubscriptions
                    SET payment_status = 'paid', status = 'active', start_date = :start, end_date = :end
                    WHERE id = :id");
                $upd2->bindParam(':start', $start, PDO::PARAM_STR);
                $upd2->bindParam(':end', $end, PDO::PARAM_STR);
                $upd2->bindParam(':id', $pay->reference_id, PDO::PARAM_INT);
                $upd2->execute();
            } else {
                // class_series
                $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'paid' WHERE id = :id");
                $upd2->bindParam(':id', $pay->reference_id, PDO::PARAM_INT);
                $upd2->execute();
            }
        } else {
            // Failed / timeout
            $status = ($cb['result_code'] == '1037') ? 'TIMEOUT' : 'FAILED';
            $upd = $dbh->prepare("UPDATE tblpayments
                SET status = :status, result_code = :rc, result_desc = :rd
                WHERE id = :id");
            $upd->bindParam(':status', $status, PDO::PARAM_STR);
            $upd->bindParam(':rc', $cb['result_code'], PDO::PARAM_STR);
            $upd->bindParam(':rd', $cb['result_desc'], PDO::PARAM_STR);
            $upd->bindParam(':id', $pay->id, PDO::PARAM_INT);
            $upd->execute();

            if ($pay->payment_type == 'package') {
                $upd2 = $dbh->prepare("UPDATE tblsubscriptions SET payment_status = 'failed' WHERE id = :id");
            } else {
                $upd2 = $dbh->prepare("UPDATE tblclass_enrollment SET payment_status = 'failed' WHERE id = :id");
            }
            $upd2->bindParam(':id', $pay->reference_id, PDO::PARAM_INT);
            $upd2->execute();
        }
    }
}

// Always respond success so Safaricom stops retrying
header('Content-Type: application/json');
echo json_encode(array('ResultCode' => 0, 'ResultDesc' => 'Success'));
?>
