<?php
// ============================================================
// M-Pesa Daraja API client (STK Push / Lipa Na M-Pesa Online)
// Uses cURL (available in Apache PHP).
// ============================================================

class Mpesa
{
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $callbackUrl;

    public function __construct()
    {
        $this->baseUrl       = MPESA_BASE_URL;
        $this->consumerKey   = MPESA_CONSUMER_KEY;
        $this->consumerSecret = MPESA_CONSUMER_SECRET;
        $this->passkey       = MPESA_PASSKEY;
        $this->shortcode     = MPESA_SHORTCODE;
        $this->callbackUrl   = MPESA_CALLBACK_URL;
    }

    // Get OAuth access token
    public function getAccessToken()
    {
        $url = $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials';
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return array('error' => $err);
        }
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            return array('access_token' => $data['access_token']);
        }
        return array('error' => isset($data['errorMessage']) ? $data['errorMessage'] : 'Token request failed');
    }

    // Initiate STK Push
    public function stkPush($phone, $amount, $accountRef, $desc)
    {
        $token = $this->getAccessToken();
        if (isset($token['error'])) {
            return array('error' => $token['error']);
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = array(
            'BusinessShortCode' => $this->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int) round($amount),
            'PartyA'            => $phone,
            'PartyB'            => $this->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->callbackUrl,
            'AccountReference'  => $accountRef,
            'TransactionDesc'   => $desc
        );

        $url = $this->baseUrl . '/mpesa/stkpush/v1/processrequest';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token['access_token'],
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return array('error' => $err);
        }
        return json_decode($response, true);
    }

    // Query STK push status (fallback for "Check Payment Status")
    public function queryStatus($checkoutRequestId)
    {
        $token = $this->getAccessToken();
        if (isset($token['error'])) {
            return array('error' => $token['error']);
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = array(
            'BusinessShortCode'    => $this->shortcode,
            'Password'             => $password,
            'Timestamp'            => $timestamp,
            'CheckoutRequestID'    => $checkoutRequestId
        );

        $url = $this->baseUrl . '/mpesa/stkpushquery/v1/query';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token['access_token'],
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return array('error' => $err);
        }
        return json_decode($response, true);
    }

    // Parse STK callback body into a normalized array
    public function handleCallback($json)
    {
        $data = json_decode($json, true);
        $result = array(
            'checkout_request_id' => null,
            'merchant_request_id' => null,
            'result_code'         => null,
            'result_desc'         => null,
            'receipt'             => null,
            'transaction_date'    => null,
            'phone'               => null,
            'amount'              => null
        );

        if (isset($data['Body']['stkCallback'])) {
            $cb = $data['Body']['stkCallback'];
            $result['checkout_request_id'] = isset($cb['CheckoutRequestID']) ? $cb['CheckoutRequestID'] : null;
            $result['merchant_request_id'] = isset($cb['MerchantRequestID']) ? $cb['MerchantRequestID'] : null;
            $result['result_code']         = isset($cb['ResultCode']) ? $cb['ResultCode'] : null;
            $result['result_desc']         = isset($cb['ResultDesc']) ? $cb['ResultDesc'] : null;

            if (isset($cb['CallbackMetadata']['Item']) && is_array($cb['CallbackMetadata']['Item'])) {
                foreach ($cb['CallbackMetadata']['Item'] as $item) {
                    $name = isset($item['Name']) ? $item['Name'] : '';
                    $value = isset($item['Value']) ? $item['Value'] : null;
                    switch ($name) {
                        case 'MpesaReceiptNumber':
                            $result['receipt'] = $value;
                            break;
                        case 'TransactionDate':
                            $result['transaction_date'] = $value;
                            break;
                        case 'PhoneNumber':
                            $result['phone'] = $value;
                            break;
                        case 'Amount':
                            $result['amount'] = $value;
                            break;
                    }
                }
            }
        }
        return $result;
    }
}
?>
